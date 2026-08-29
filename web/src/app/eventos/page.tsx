/*
 * SPIKE DESCARTÁVEL — BORA-32 (M0). Fora da Definition of Done (Princípio XI).
 * Objetivo único: responder as 4 perguntas do spike (SSR, CORS, 360px, fronteira
 * servidor/cliente). NÃO é padrão de tela — o padrão nasce na spec 001.
 * Apagar junto com o EventoSpikeController da api/.
 *
 * Este arquivo é um SERVER COMPONENT: não tem "use client", roda só no Node do
 * Next e o navegador nunca recebe o código dele.
 */
import type { Metadata } from "next";
import ProvaCors from "./ProvaCors";

// 127.0.0.1 e não localhost de propósito: o Node 24 pode resolver localhost para
// ::1 e o `artisan serve` está escutando só em IPv4.
const API = "http://127.0.0.1:8000/api/v1/eventos";

export const metadata: Metadata = {
  title: "Eventos (spike) — Bora",
};

type Evento = {
  id: number;
  nome: string;
  local: string;
  cidade: string;
  genero: string;
  comeca_em: string;
};

export default async function Page() {
  // Roda NO SERVIDOR. `fetch` no Next 16 não é cacheado por padrão e bloqueia a
  // renderização até responder — por isso o HTML já sai pronto (pergunta 1).
  let eventos: Evento[] = [];
  let erro: string | null = null;

  try {
    const resposta = await fetch(API);
    if (!resposta.ok) {
      throw new Error(`API respondeu ${resposta.status}`);
    }
    const corpo = (await resposta.json()) as { data: Evento[] };
    eventos = corpo.data;
  } catch (e) {
    erro = e instanceof Error ? e.message : "falha desconhecida";
  }

  return (
    <main className="mx-auto w-full max-w-2xl px-4 py-6">
      <h1 className="text-2xl font-bold">Eventos (spike)</h1>
      <p className="mt-1 text-sm opacity-80">
        Lista fixa vinda da API. Renderizado no servidor.
      </p>

      {erro ? (
        <p className="mt-6 rounded border border-red-500 p-3 text-sm">
          Não deu para carregar os eventos agora ({erro}). A API está de pé em{" "}
          <code className="break-all">{API}</code>?
        </p>
      ) : (
        <ul className="mt-6 flex flex-col gap-3">
          {eventos.map((evento) => (
            <li key={evento.id} className="rounded border p-3">
              <h2 className="text-lg font-semibold break-words">{evento.nome}</h2>
              <p className="text-sm break-words">
                {evento.local} · {evento.cidade}
              </p>
              <p className="text-sm opacity-80">
                {evento.genero} ·{" "}
                <time dateTime={evento.comeca_em}>{evento.comeca_em}</time>
              </p>
            </li>
          ))}
        </ul>
      )}

      <ProvaCors endpoint={API} />
    </main>
  );
}
