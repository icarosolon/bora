<?php

namespace App\Providers;

use App\Adapters\Email\MailerEnviadorDeEmail;
use App\Domain\Account\PoliticaDeSenha;
use App\Domain\Account\PoliticaDeSessao;
use App\Ports\EnviadorDeEmail;
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
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute($porMinuto)->by($email.'|'.$request->ip()),
                // Teto por IP, mais folgado: contém varredura de muitos e-mails
                // a partir da mesma origem sem travar uso legítimo compartilhado.
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
