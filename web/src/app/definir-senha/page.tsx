'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { FormularioBase, estadoInicial, type EstadoEnvio } from '@/components/auth/FormularioBase'
import { LayoutAuth } from '@/components/auth/LayoutAuth'
import { Aviso } from '@/components/ui/aviso'
import { Campo } from '@/components/ui/campo'
import { chamarApi, erroDoCampo } from '@/lib/api'
import { estaAutenticado } from '@/lib/sessao'

/**
 * Definir a primeira senha (US2-5, decisão D1 — direção inversa).
 *
 * Para quem entrou pelo Google e quer também poder entrar com e-mail e senha.
 * A confirmação do titular é a **sessão ativa**: por isso a tela só faz sentido
 * autenticada, e quem chega sem sessão é mandado para o login.
 */
export default function DefinirSenha() {
  const router = useRouter()
  const [estado, setEstado] = useState<EstadoEnvio>(estadoInicial)
  const [verificandoSessao, setVerificandoSessao] = useState(true)

  useEffect(() => {
    if (!estaAutenticado()) {
      router.replace('/entrar')
      return
    }
    setVerificandoSessao(false)
  }, [router])

  async function enviar(evento: React.FormEvent<HTMLFormElement>) {
    evento.preventDefault()
    if (estado.enviando) return

    const dados = new FormData(evento.currentTarget)
    setEstado({ ...estadoInicial, enviando: true })

    const resultado = await chamarApi('/senha', {
      metodo: 'POST',
      corpo: { senha: dados.get('senha') },
      autenticado: true,
    })

    if (resultado.tipo === 'ok') {
      setEstado({
        ...estadoInicial,
        sucesso: resultado.mensagem ?? 'Senha definida.',
      })
      return
    }

    if (resultado.tipo === 'validacao') {
      setEstado({ ...estadoInicial, erros: resultado.erros })
      return
    }

    if (resultado.tipo === 'nao_autenticado') {
      router.replace('/entrar')
      return
    }

    setEstado({ ...estadoInicial, erroGeral: resultado.mensagem })
  }

  if (verificandoSessao) {
    return (
      <LayoutAuth titulo="Definir senha">
        <Aviso tipo="informacao">Carregando…</Aviso>
      </LayoutAuth>
    )
  }

  return (
    <LayoutAuth
      titulo="Definir senha"
      subtitulo="Assim você poderá entrar com o Google ou com sua senha, como preferir."
    >
      <FormularioBase
        estado={estado}
        rotuloAcao="Salvar senha"
        rotuloEnviando="Salvando…"
        onSubmit={enviar}
      >
        <Campo
          name="senha"
          type="password"
          rotulo="Nova senha"
          autoComplete="new-password"
          dica="Pelo menos 8 caracteres."
          erro={erroDoCampo(estado.erros, 'senha')}
        />
      </FormularioBase>
    </LayoutAuth>
  )
}
