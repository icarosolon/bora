import type { Page } from '@playwright/test'

/**
 * Fonte do sistema ampliada — o cenário que NENHUMA media query de largura vê.
 *
 * O `ux-requirements.md` pede duas coisas que parecem a mesma e não são:
 * "compatível com zoom de 200%" e "a interface respeita o ajuste de tamanho de
 * fonte do aparelho/navegador". A D12 registrou a diferença:
 *
 * | cenário a 200%      | viewport      | fonte |
 * |---------------------|---------------|-------|
 * | zoom do navegador   | 360 → ~180    | 16px  |
 * | fonte do sistema    | continua 360  | 32px  |
 *
 * Reduzir a largura da janela **não** reproduz o segundo. É por isso que ele
 * tem projeto próprio, com o mesmo viewport de 360.
 *
 * Não existe opção do Playwright para o tamanho de fonte padrão do navegador —
 * só o protocolo do DevTools tem. `Page.setFontSizes` é exatamente o que o
 * Chromium muda quando o sistema operacional pede fonte maior: altera o valor
 * de `font-size: medium`, e portanto o `rem`, sem tocar no viewport.
 *
 * Consequência que interessa ao portão: página que fixa `html { font-size }`
 * em pixel ignora esta configuração. Isso **não** é um limite da ferramenta —
 * é o defeito sendo encontrado, e por isso o portão confere se a raiz
 * realmente cresceu antes de medir qualquer outra coisa.
 */
export const PROJETO_FONTE_AMPLIADA = 'fonte-ampliada-360'

/** 32px = 200% do padrão de 16px, que é o alvo do `ux-requirements.md`. */
export const FONTE_AMPLIADA_PX = 32

export async function aplicarFonteDoSistemaAmpliada(page: Page): Promise<void> {
  const cdp = await page.context().newCDPSession(page)
  await cdp.send('Page.enable')
  await cdp.send('Page.setFontSizes', {
    fontSizes: { standard: FONTE_AMPLIADA_PX, fixed: FONTE_AMPLIADA_PX },
  })
}
