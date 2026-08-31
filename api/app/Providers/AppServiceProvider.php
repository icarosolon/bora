<?php

namespace App\Providers;

use App\Adapters\Email\MailerEnviadorDeEmail;
use App\Adapters\Socialite\GoogleIdentidade;
use App\Domain\Account\PoliticaDeSenha;
use App\Domain\Account\PoliticaDeSessao;
use App\Ports\EnviadorDeEmail;
use App\Ports\ProvedorDeIdentidade;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Amarra as políticas do domínio aos parâmetros de configuração.
     *
     * Princípio VII: a política vive no domínio e não conhece o framework; é
     * aqui, na borda, que o número sai de config/bora.php e entra na classe.
     */
    public function register(): void
    {
        $this->app->singleton(
            PoliticaDeSenha::class,
            fn () => new PoliticaDeSenha((int) config('bora.conta.senha_minima'))
        );

        $this->app->singleton(
            PoliticaDeSessao::class,
            fn () => new PoliticaDeSessao((int) config('bora.sessao.validade_dias'))
        );

        // Porta -> adapter (Principio VII). O provedor concreto (log em dev,
        // Resend em producao) e escolhido por MAIL_MAILER, nao por codigo.
        $this->app->bind(EnviadorDeEmail::class, MailerEnviadorDeEmail::class);

        // Idem para o login social: quem conhece o Socialite é só o adapter.
        // É esta amarração que os testes da US2 trocam por um provedor falso,
        // para exercitar cancelamento e falha sem depender do Google.
        $this->app->bind(ProvedorDeIdentidade::class, GoogleIdentidade::class);
    }

    public function boot(): void
    {
        $this->registrarLimitesDeTentativa();
    }

    /**
     * Limites de tentativa (FR-007).
     *
     * A chave combina e-mail + IP: só IP puniria gente inocente atrás do mesmo
     * NAT (um bar com wi-fi compartilhado), e só e-mail deixaria um atacante
     * varrer muitas contas a partir de uma máquina.
     *
     * O MESMO limitador vale para login e para a confirmação de união: a spec
     * exige o bloqueio nas duas portas, senão a união vira o caminho livre para
     * adivinhar a senha.
     */
    private function registrarLimitesDeTentativa(): void
    {
        $porMinuto = (int) config('bora.tentativas.por_minuto');

        RateLimiter::for('autenticacao', function (Request $request) use ($porMinuto) {
            /*
             * O balde é POR ALVO, não um só por origem.
             *
             * O login manda `email`; a união manda `uniao_token` (que identifica
             * a conta-alvo) e não manda e-mail nenhum. Chavear só pelo e-mail
             * fazia todas as rotas sem esse campo colapsarem num balde único por
             * IP — e aí uma pessoa que entra pelo Google e depois confirma a
             * união se trancava sozinha, misturando fluxos que nada têm a ver.
             *
             * Sem alvo identificável, sobra o IP — que já tem o teto abaixo.
             */
            $alvo = mb_strtolower(trim((string) $request->input('email')))
                ?: (string) $request->input('uniao_token')
                ?: 'sem-alvo';

            return [
                Limit::perMinute($porMinuto)->by($alvo.'|'.$request->ip()),
                // Teto por IP, mais folgado: contém varredura de muitos alvos a
                // partir da mesma origem sem travar uso legítimo compartilhado
                // (um bar com wi-fi para todo mundo, que é o cenário do Bora).
                Limit::perMinute($porMinuto * 4)->by($request->ip()),
            ];
        });

        // Envio de e-mail (recuperação, reenvio de verificação, link de união):
        // limite mais apertado, porque cada acerto custa um e-mail de verdade.
        RateLimiter::for('envio-de-email', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(3)->by($email.'|'.$request->ip()),
                Limit::perHour(10)->by($request->ip()),
            ];
        });
    }
}
