'use client'

import { useEffect, useState } from 'react'

/**
 * `false` até o componente montar no navegador; `true` depois.
 *
 * POR QUE ISTO EXISTE (e por que virou hook em vez de ficar copiado):
 *
 * Entre o HTML chegar e o React hidratar existe uma janela em que a tela
 * **parece** pronta e não é. O que acontece nessa janela depende do controle:
 *
 * - `<button type="submit">` em formulário: o navegador submete NATIVAMENTE, em
 *   GET, com os campos na query string — ou seja, **a senha vai para a URL**
 *   (E-012).
 * - `<button type="button">` com `onClick`: o toque é **silenciosamente
 *   ignorado**. A pessoa aperta e nada acontece, sem qualquer retorno — o que o
 *   `ux-requirements.md` proíbe explicitamente ("toda ação responde na hora...
 *   nunca silêncio").
 *
 * O segundo caso escapou três vezes seguidas: no formulário, no botão do Google
 * e no plano B da união. Sempre pego por teste e2e, nunca pelo teste de
 * componente — jsdom não tem essa janela.
 *
 * Por isso: **todo controle que dispara ação usa este hook**, desabilitando-se
 * e dizendo "Carregando…" até ficar pronto. A janela é curta num computador
 * rápido, mas o público do Bora usa aparelho modesto em rede lenta, onde ela é
 * bem real.
 */
export function useHidratado(): boolean {
  const [hidratado, setHidratado] = useState(false)

  useEffect(() => setHidratado(true), [])

  return hidratado
}
