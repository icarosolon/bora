'use client'

import { useEffect, useState } from 'react'
import { useSearchParams } from 'next/navigation'
import Link from 'next/link'
import { Suspense } from 'react'
import { LayoutAuth } from '@/components/auth/LayoutAuth'
import { Aviso } from '@/components/ui/aviso'
import { Button } from '@/components/ui/button'
import { chamarApi } from '@/lib/api'
import { estaAutenticado } from '@/lib/sessao'

type Situacao =
  | { estado: 'verificando' }
  | { estado: 'confirmado'; mensagem: string }
  | { estado: 'expirado'; mensagem: string }
  | { estado: 'falha'; mensagem: string }

function Conteudo() {
  const parametros = useSearchParams()
  const token = parametros.get('token')
  const [situacao, setSituacao] = useState<Situacao>({ estado: 'verificando' })
  const [reenviando, setReenviando] = useState(false)
  const [reenviado, setReenviado] = useState<string | null>(null)

  /*
   * Estado de sessão em `useState` + `useEffect`, e NÃO chamando
   * `estaAutenticado()` direto no JSX.
   *
   * A função lê `localStorage`, que não existe no servidor: ela devolvia
   * `false` na renderização do servidor e podia devolver `true` na hidratação.
   * O React reclamava de HTML divergente e **desistia de corrigir aquela
   * subárvore** — a tela ficava com o estado errado em silêncio.
   *
   * Regra que vale para toda tela deste projeto: nada que dependa do navegador
   * (localStorage, window, data/hora) pode ser lido durante a renderização.
   */
  const [autenticado, setAutenticado] = useState(false)
  useEffect(() => setAutenticado(estaAutenticado()), [])

  useEffect(() => {
    if (!token) {
      setSituacao({
        estado: 'falha',
        mensagem: 'Link incompleto. Abra o link direto do e-mail que enviamos.',
      })
      return
    }

    chamarApi('/email/verificar', { metodo: 'POST', corpo: { token } }).then((r) => {
      if (r.tipo === 'ok') {
        setSituacao({ estado: 'confirmado', mensagem: r.mensagem ?? 'E-mail confirmado.' })
      } else if (r.tipo === 'expirado') {
        setSituacao({ estado: 'expirado', mensagem: r.mensagem })
      } else {
        setSituacao({ estado: 'falha', mensagem: r.mensagem })
      }
    })
  }, [token])

  async function reenviar() {
    setReenviando(true)
    const r = await chamarApi('/email/verificar/reenviar', {
      metodo: 'POST',
      autenticado: true,
    })
    setReenviando(false)
    setReenviado(
      r.tipo === 'ok'
        ? (r.mensagem ?? 'Enviamos um novo link.')
        : r.mensagem,
    )
  }

  return (
    <LayoutAuth titulo="Confirmar e-mail">
      {situacao.estado === 'verificando' && (
        <Aviso tipo="informacao">Confirmando seu e-mail…</Aviso>
      )}

      {situacao.estado === 'confirmado' && (
        <>
          <Aviso tipo="sucesso">{situacao.mensagem}</Aviso>
          <p className="mt-6">
            <Link href="/" className="font-medium underline underline-offset-4">
              Ir para o Bora
            </Link>
          </p>
        </>
      )}

      {(situacao.estado === 'expirado' || situacao.estado === 'falha') && (
        <>
          <Aviso tipo="erro">{situacao.mensagem}</Aviso>

          {/* Só oferece reenvio a quem está logado: o endpoint exige sessão. */}
          {autenticado ? (
            <>
              <Button
                type="button"
                onClick={reenviar}
                disabled={reenviando}
                className="mt-6 min-h-11 w-full text-base"
              >
                {reenviando ? 'Enviando…' : 'Enviar um novo link'}
              </Button>
              {reenviado && (
                <div className="mt-4">
                  <Aviso tipo="informacao">{reenviado}</Aviso>
                </div>
              )}
            </>
          ) : (
            <p className="mt-6">
              <Link href="/entrar" className="font-medium underline underline-offset-4">
                Entre na sua conta
              </Link>{' '}
              para pedir um novo link.
            </p>
          )}
        </>
      )}
    </LayoutAuth>
  )
}

/**
 * Confirmação de e-mail pelo link (US1-7).
 *
 * `useSearchParams` exige Suspense no App Router. Aqui isso não custa nada:
 * a página é da área logada/transacional, não do catálogo público — não há
 * requisito de SEO a proteger (a armadilha que o spike BORA-32 registrou vale
 * para as páginas de catálogo).
 */
export default function VerificarEmail() {
  return (
    <Suspense fallback={<LayoutAuth titulo="Confirmar e-mail"><Aviso tipo="informacao">Carregando…</Aviso></LayoutAuth>}>
      <Conteudo />
    </Suspense>
  )
}
