'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { FormularioBase, estadoInicial, type EstadoEnvio } from '@/components/auth/FormularioBase'
import { Aviso } from '@/components/ui/aviso'
import { Button } from '@/components/ui/button'
import { Campo } from '@/components/ui/campo'
import { chamarApi, erroDoCampo } from '@/lib/api'
import { useHidratado } from '@/lib/hidratacao'
import { consumirDestino, guardarToken } from '@/lib/sessao'

type Sessao = { conta: { id: number; nome: string }; token: string; expira_em: string }

/**
 * Confirmação da união de credenciais (US3, decisão D1).
 *
 * A tela precisa **explicar o que vai acontecer** antes de pedir a senha: a
 * pessoa entrou pelo Google e foi parar num pedido de senha, o que assusta se
 * não for justificado. É o `ux-requirements.md` na prática — "cada tela se
 * explica sozinha ou falhou".
 *
 * O plano B (link por e-mail) fica visível desde o começo, não escondido atrás
 * de um erro: quem já sabe que não lembra a senha não deveria precisar errar
 * primeiro para descobrir que existe saída.
 */
export function FormularioUnirContas({ token, email }: { token: string; email: string }) {
  const router = useRouter()
  const [estado, setEstado] = useState<EstadoEnvio>(estadoInicial)
  const [enviandoLink, setEnviandoLink] = useState(false)
  const [linkEnviado, setLinkEnviado] = useState<string | null>(null)

  // O botão do plano B também dispara ação: antes da hidratação o toque seria
  // ignorado em silêncio. Ver `useHidratado`.
  const pronto = useHidratado()

  async function confirmarComSenha(evento: React.FormEvent<HTMLFormElement>) {
    evento.preventDefault()
    if (estado.enviando) return

    const dados = new FormData(evento.currentTarget)
    setEstado({ ...estadoInicial, enviando: true })

    const resultado = await chamarApi<Sessao>('/uniao-credenciais', {
      metodo: 'POST',
      corpo: { uniao_token: token, senha: dados.get('senha') },
    })

    if (resultado.tipo === 'ok') {
      guardarToken(resultado.dados.token)
      router.replace(consumirDestino() ?? '/')
      return
    }

    if (resultado.tipo === 'validacao') {
      setEstado({ ...estadoInicial, erros: resultado.erros })
      return
    }

    // 401 (senha errada), 410 (pedido expirado) e 429 (limite) trazem mensagem
    // da API — a mesma que o app mobile mostraria.
    setEstado({ ...estadoInicial, erroGeral: resultado.mensagem })
  }

  async function pedirLink() {
    if (enviandoLink) return

    setEnviandoLink(true)
    const resultado = await chamarApi('/uniao-credenciais/link', {
      metodo: 'POST',
      corpo: { uniao_token: token },
    })
    setEnviandoLink(false)

    setLinkEnviado(
      resultado.tipo === 'ok'
        ? (resultado.mensagem ?? 'Enviamos um link para o seu e-mail.')
        : resultado.mensagem,
    )
  }

  return (
    <div className="flex flex-col gap-6">
      <Aviso tipo="informacao">
        Você já tem uma conta no Bora com <strong>{email}</strong>. Confirme que é você e as
        duas formas de entrar passam a valer para a mesma conta — nada é duplicado.
      </Aviso>

      <FormularioBase
        estado={estado}
        rotuloAcao="Unir e entrar"
        rotuloEnviando="Unindo…"
        onSubmit={confirmarComSenha}
      >
        <Campo
          name="senha"
          type="password"
          rotulo="Sua senha do Bora"
          autoComplete="current-password"
          erro={erroDoCampo(estado.erros, 'senha')}
        />
      </FormularioBase>

      <div className="flex flex-col gap-3">
        <p className="text-base text-muted-foreground">Não lembra a senha?</p>

        <Button
          type="button"
          variant="outline"
          onClick={pedirLink}
          disabled={enviandoLink || !pronto}
          aria-busy={enviandoLink || !pronto}
          className="min-h-11 w-full text-base"
        >
          {enviandoLink ? 'Enviando…' : pronto ? 'Receber link por e-mail' : 'Carregando…'}
        </Button>

        {linkEnviado && <Aviso tipo="informacao">{linkEnviado}</Aviso>}
      </div>
    </div>
  )
}
