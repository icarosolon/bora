<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Auditoria de escrita (Princípio VIII, RN-PLAT-004).
 *
 * REGRA INEGOCIÁVEL, e é o motivo de esta classe existir em vez de cada caso de
 * uso chamar o activity() direto: o log registra QUE o evento aconteceu, QUEM o
 * fez e QUANDO — nunca o valor. Senha, hash de senha, token em claro e token
 * hash são PROIBIDOS aqui (Princípio V). Por isso as propriedades passam por
 * uma lista de chaves barradas antes de gravar: um descuido num caso de uso não
 * consegue vazar segredo para o banco de auditoria.
 *
 * Há teste que percorre os fluxos e falha se algo sensível aparecer no log.
 */
final class AuditLog
{
    /** Chaves que nunca podem ser gravadas, mesmo se alguém as passar. */
    private const FORBIDDEN_KEYS = [
        'password', 'current_password', 'new_password', 'password_confirmation',
        'token', 'token_hash', 'plain_text_token', 'access_token', 'merge_token',
        'client_secret', 'secret', 'remember_token', 'code',
    ];

    public const ACCOUNT_CREATED = 'account_created';

    public const CREDENTIALS_MERGED = 'credentials_merged';

    public const PASSWORD_SET = 'password_set';

    public const PASSWORD_RESET = 'password_reset';

    public const EMAIL_VERIFIED = 'email_verified';

    public const SESSION_ENDED = 'session_ended';

    /**
     * @param  array<string, mixed>  $properties  contexto NÃO sensível
     */
    public static function record(
        string $event,
        ?User $subject = null,
        ?User $causer = null,
        array $properties = [],
    ): void {
        $log = activity('account')
            ->event($event)
            ->withProperties(self::scrub($properties));

        if ($subject !== null) {
            $log->performedOn($subject);
        }

        // Autor explícito quando houver; senão o usuário autenticado. Ação sem
        // autor (ex.: confirmação por link, sem sessão) fica registrada como
        // tal, em vez de atribuir a alguém errado.
        if ($causer !== null) {
            $log->causedBy($causer);
        }

        $log->log($event);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private static function scrub(array $properties): array
    {
        foreach (array_keys($properties) as $key) {
            $normalized = mb_strtolower((string) $key);

            foreach (self::FORBIDDEN_KEYS as $forbidden) {
                if (str_contains($normalized, $forbidden)) {
                    unset($properties[$key]);
                    break;
                }
            }
        }

        return $properties;
    }

    /**
     * Só para teste: o log tem alguma propriedade sensível?
     *
     * Inspeciona **chaves**, não valores. A primeira versão varria o JSON
     * inteiro e acusava falso positivo em conteúdo legítimo — `confirmado_via:
     * "senha"` diz apenas por qual caminho a união foi confirmada, e não é
     * segredo nenhum. Detector que grita sem motivo é pior que detector nenhum:
     * ensina a ignorá-lo.
     */
    public static function containsSensitiveData(Activity $activity): bool
    {
        return self::hasForbiddenKey((array) $activity->properties->toArray());
    }

    /** @param array<mixed, mixed> $data */
    private static function hasForbiddenKey(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $normalized = mb_strtolower($key);

                foreach (self::FORBIDDEN_KEYS as $forbidden) {
                    if (str_contains($normalized, $forbidden)) {
                        return true;
                    }
                }
            }

            if (is_array($value) && self::hasForbiddenKey($value)) {
                return true;
            }
        }

        return false;
    }
}
