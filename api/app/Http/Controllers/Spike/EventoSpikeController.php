<?php

namespace App\Http\Controllers\Spike;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * ANDAIME DESCARTÁVEL — spike BORA-32 (M0).
 *
 * Existe só para o spike do frontend ter um GET real para consumir enquanto se
 * absorve a curva de Next/React/CORS. NÃO é feature: não tem banco, não tem
 * auth, não tem API Resource e está fora da Definition of Done (Princípio XI).
 * A lista de eventos de verdade nasce na spec do catálogo, aí sim com Resource,
 * paginação e envelope completo (Princípio IV).
 *
 * Apagar junto com a página /eventos do web/ quando o spike for encerrado.
 */
class EventoSpikeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'id' => 1,
                    'nome' => 'Forró do Velho Chico',
                    'local' => 'Bar do Cais',
                    'cidade' => 'Juazeiro-BA',
                    'genero' => 'Forró',
                    'comeca_em' => '2026-09-04T21:00:00-03:00',
                ],
                [
                    'id' => 2,
                    'nome' => 'Samba na Beira do Rio',
                    'local' => 'Quintal da Orla',
                    'cidade' => 'Petrolina-PE',
                    'genero' => 'Samba',
                    'comeca_em' => '2026-09-05T20:30:00-03:00',
                ],
                [
                    'id' => 3,
                    'nome' => 'Rock do Sertão',
                    'local' => 'Galpão 66',
                    'cidade' => 'Petrolina-PE',
                    'genero' => 'Rock',
                    'comeca_em' => '2026-09-06T22:00:00-03:00',
                ],
            ],
        ]);
    }
}
