<?php

namespace App\Providers;

use App\Adapters\Email\MailerEmailSender;
use App\Adapters\Socialite\GoogleIdentityProvider;
use App\Domain\Account\PasswordPolicy;
use App\Domain\Account\SessionPolicy;
use App\Ports\EmailSender;
use App\Ports\IdentityProvider;
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
            PasswordPolicy::class,
            fn () => new PasswordPolicy((int) config('bora.account.minimum_password_length'))
        );

        $this->app->singleton(
            SessionPolicy::class,
            fn () => new SessionPolicy((int) config('bora.session.lifetime_days'))
        );

        // Porta -> adapter (Principio VII). O provedor concreto (log em dev,
        // Resend em producao) e escolhido por MAIL_MAILER, nao por codigo.
        $this->app->bind(EmailSender::class, MailerEmailSender::class);

        // Idem para o login social: quem conhece o Socialite é só o adapter.
        // É esta amarração que os testes da US2 trocam por um provedor falso,
        // para exercitar cancelamento e falha sem depender do Google.
        $this->app->bind(IdentityProvider::class, GoogleIdentityProvider::class);
    }

    public function boot(): void
    {
        $this->registerRateLimits();
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
    private function registerRateLimits(): void
    {
        $perMinute = (int) config('bora.attempts.per_minute');

        RateLimiter::for('authentication', function (Request $request) use ($perMinute) {
            /*
             * O balde é POR ALVO, não um só por origem.
             *
             * O login manda `email`; a união manda `merge_token` (que identifica
             * a conta-alvo) e não manda e-mail nenhum. Chavear só pelo e-mail
             * fazia todas as rotas sem esse campo colapsarem num balde único por
             * IP — e aí uma pessoa que entra pelo Google e depois confirma a
             * união se trancava sozinha, misturando fluxos que nada têm a ver.
             *
             * Sem alvo identificável, sobra o IP — que já tem o teto abaixo.
             */
            $target = mb_strtolower(trim((string) $request->input('email')))
                ?: (string) $request->input('merge_token')
                ?: 'no-target';

            return [
                Limit::perMinute($perMinute)->by($target.'|'.$request->ip()),
                // Teto por IP, mais folgado: contém varredura de muitos alvos a
                // partir da mesma origem sem travar uso legítimo compartilhado
                // (um bar com wi-fi para todo mundo, que é o cenário do Bora).
                Limit::perMinute($perMinute * 4)->by($request->ip()),
            ];
        });

        // Envio de e-mail (recuperação, reenvio de verificação, link de união):
        // limite mais apertado, porque cada acerto custa um e-mail de verdade.
        RateLimiter::for('email-sending', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(3)->by($email.'|'.$request->ip()),
                Limit::perHour(10)->by($request->ip()),
            ];
        });
    }
}
