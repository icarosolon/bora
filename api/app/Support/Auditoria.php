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
final class Auditoria
{
    /** Chaves que nunca podem ser gravadas, mesmo se alguém as passar. */
    private const PROIBIDAS = [
        'password', 'senha', 'senha_atual', 'nova_senha', 'password_confirmation',
        'token', 'token_hash', 'plain_text_token', 'access_token', 'uniao_token',
        'client_secret', 'secret', 'remember_token', 'code',
    ];

    public const CONTA_CRIADA = 'conta_criada';

    public const CREDENCIAIS_UNIDAS = 'credenciais_unidas';

    public const SENHA_DEFINIDA = 'senha_definida';

    public const SENHA_REDEFINIDA = 'senha_redefinida';

    public const EMAIL_VERIFICADO = 'email_verificado';

    public const SESSAO_ENCERRADA = 'sessao_encerrada';

    /**
     * @param  array<string, mixed>  $propriedades  contexto NÃO sensível
     */
    public static function registrar(
        string $evento,
        ?User $sobre = null,
        ?User $autor = null,
        array $propriedades = [],
    ): void {
        $log = activity('conta')
            ->event($evento)
            ->withProperties(self::limpar($propriedades));

        if ($sobre !== null) {
            $log->performedOn($sobre);
        }

        // Autor explícito quando houver; senão o usuário autenticado. Ação sem
        // autor (ex.: confirmação por link, sem sessão) fica registrada como
        // tal, em vez de atribuir a alguém errado.
        if ($autor !== null) {
            $log->causedBy($autor);
        }

        $log->log($evento);
    }

    /**
     * @param  array<string, mixed>  $propriedades
     * @return array<string, mixed>
     */
    private static function limpar(array $propriedades): array
    {
        foreach (array_keys($propriedades) as $chave) {
            $normalizada = mb_strtolower((string) $chave);

            foreach (self::PROIBIDAS as $proibida) {
                if (str_contains($normalizada, $proibida)) {
                    unset($propriedades[$chave]);
                    break;
                }
            }
        }

        return $propriedades;
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
    public static function contemDadoSensivel(Activity $atividade): bool
    {
        return self::temChaveProibida((array) $atividade->properties->toArray());
    }

    /** @param array<mixed, mixed> $dados */
    private static function temChaveProibida(array $dados): bool
    {
        foreach ($dados as $chave => $valor) {
            if (is_string($chave)) {
                $normalizada = mb_strtolower($chave);

                foreach (self::PROIBIDAS as $proibida) {
                    if (str_contains($normalizada, $proibida)) {
                        return true;
                    }
                }
            }

            if (is_array($valor) && self::temChaveProibida($valor)) {
                return true;
            }
        }

        return false;
    }
}
