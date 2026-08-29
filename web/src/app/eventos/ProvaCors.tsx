"use client";

/*
 * SPIKE DESCARTÁVEL — BORA-32. Existe só para provar a pergunta 2 do spike:
 * a chamada precisa partir DO NAVEGADOR, não do servidor do Next.
 * O "use client" acima é a fronteira: daqui para baixo o código é enviado ao
 * navegador e executa lá. Por isso este arquivo pode ter useState/onClick e o
 * page.tsx não pode.
 */
import { useState } from "react";

type Estado =
  | { situacao: "parado" }
  | { situacao: "carregando" }
  | { situacao: "ok"; quantidade: number; primeiro: string }
  | { situacao: "erro"; mensagem: string };

export default function ProvaCors({ endpoint }: { endpoint: string }) {
  const [estado, setEstado] = useState<Estado>({ situacao: "parado" });

  async function chamar() {
    setEstado({ situacao: "carregando" });
    try {
      const resposta = await fetch(endpoint);
      const corpo = await resposta.json();
      setEstado({
        situacao: "ok",
        quantidade: corpo.data.length,
        primeiro: corpo.data[0].nome,
      });
    } catch (e) {
      setEstado({
        situacao: "erro",
        mensagem: e instanceof Error ? e.message : "falha desconhecida",
      });
    }
  }

  return (
    <section className="mt-8 rounded border border-dashed p-3">
      <h2 className="text-base font-semibold">Prova de CORS</h2>
      <p className="mt-1 text-sm opacity-80">
        Este botão chama a API direto do navegador.
      </p>
      <button
        type="button"
        onClick={chamar}
        className="mt-3 min-h-11 w-full rounded border px-4 py-2 font-semibold"
      >
        Chamar a API pelo navegador
      </button>
      <p className="mt-3 text-sm break-words" role="status">
        {estado.situacao === "parado" && "Ainda não chamei."}
        {estado.situacao === "carregando" && "Chamando…"}
        {estado.situacao === "ok" &&
          `CORS OK: ${estado.quantidade} eventos, o primeiro é "${estado.primeiro}".`}
        {estado.situacao === "erro" && `Falhou: ${estado.mensagem}`}
      </p>
    </section>
  );
}
