import AxeBuilder from '@axe-core/playwright'
import type { Page } from '@playwright/test'
import { expect, test } from '../base'
import { TELAS } from './telas'
import {
  FONTE_AMPLIADA_PX,
  PROJETO_FONTE_AMPLIADA,
  aplicarFonteDoSistemaAmpliada,
} from './fonte'

/**
 * PORTÃO DE CONFORMIDADE DE TELA — camada 4 do template (T005 a T009).
 *
 * A régua que este arquivo afere é `docs/product/ux-requirements.md`, que é
 * **vinculante** (Princípio XII). O `design-system.md` **não** é: enquanto o
 * próprio arquivo disser "decisões travadas, norma ainda não escrita", ele
 * explica a intenção e não vira requisito. Conflito entre os dois resolve-se
 * pelo `ux-requirements.md`, e o conflito vai para o backlog — nunca para uma
 * asserção daqui.
 *
 * O portão vem ANTES do kit por decisão D4, e a razão está no E-012: rede de
 * proteção não verificada é rede que dá falsa confiança, pior que não ter.
 * Contrato escrito depois do kit nasce moldado ao kit e não reprova ninguém.
 *
 * **Cada tela é UM teste**, e as regras entram por asserção leve
 * (`expect.soft`) de propósito: o portão precisa dizer TUDO o que a tela tem
 * de errado numa passada, não parar no primeiro achado. Portão que reprova por
 * uma coisa e esconde as outras seis faz o trabalho parecer menor do que é.
 *
 * ---
 *
 * **O que este portão NÃO cobre, dito em voz alta para ninguém confundir
 * "passou" com "está certo":**
 *
 * - *"Informação nunca transmitida só por cor"* só é automatizável em parte.
 *   Aqui se afere o que tem forma mecânica — faixa de aviso sem texto e link
 *   dentro de texto distinguível só pela cor. O resto (um ponto colorido que
 *   significa "aberto", uma linha vermelha que significa "esgotado") depende
 *   de saber o que a cor quer dizer, e isso é olho humano.
 * - *"Cada tela se explica sozinha"*, *"linguagem simples"*, *"o estado vazio
 *   ensina"* e *"a ação principal é óbvia"* não têm asserção possível. São a
 *   validação visual do Ícaro (Princípio XI), e o portão não a substitui.
 * - *"A ação principal fica na metade inferior"* também não entra: exige saber
 *   QUAL é a ação principal da tela, e isso está na spec, não no DOM.
 * - *Contraste WCAG AA* fica por conta do `axe`, que só mede o que consegue
 *   resolver: texto sobre imagem, gradiente ou pseudo-elemento ele marca como
 *   indeterminado e **não reprova**.
 * - O portão mede a tela no estado em que ela CARREGA. Estado de carregando,
 *   de erro de rede e de formulário preenchido não passam por aqui.
 */

/** `ux-requirements.md`: "Alvos de toque >= 44x44px". */
const ALVO_MINIMO_PX = 44

/** `ux-requirements.md`: "Fonte base >= 16px". */
const FONTE_MINIMA_PX = 16

/** Quantas paradas de teclado o portão percorre atrás de foco invisível. */
const MAXIMO_DE_PARADAS = 40

type Achado = { alvo: string; detalhe: string }

type Medicao = {
  larguraDoDocumento: number
  larguraVisivel: number
  estourandoALargura: Achado[]
  fonteDoCorpoPx: number
  fonteDaRaizPx: number
  textoMiudo: Achado[]
  alvoPequeno: Achado[]
  iconeSemRotulo: Achado[]
  informacaoSoPorCor: Achado[]
}

/**
 * Tudo o que se mede pelo DOM sai de uma passada só, dentro do navegador.
 *
 * Uma chamada em vez de dezenas: cada ida e volta ao navegador custa, e o
 * portão roda em quatro configurações vezes nove telas.
 */
async function medir(page: Page): Promise<Medicao> {
  return page.evaluate(
    ({ alvoMinimo, fonteMinima }) => {
      const SELETOR_INTERATIVO = [
        'a[href]',
        'button',
        'input:not([type="hidden"])',
        'select',
        'textarea',
        'summary',
        '[role="button"]',
        '[role="link"]',
        '[tabindex]:not([tabindex="-1"])',
      ].join(',')

      /*
       * A sobreposição de erro do `next dev` (`<nextjs-portal>`) entra no DOM
       * e na ordem de tabulação, e não existe no site construído. Medi-la é
       * medir a ferramenta, não o produto: ela sozinha produzia um achado de
       * foco em CADA uma das 36 combinações, o que afogaria os achados reais.
       *
       * Mesma natureza do filtro de ruído que o `base.ts` já aplica ao console.
       */
      function eDoNavegadorDeDesenvolvimento(el: Element): boolean {
        return el.tagName === 'NEXTJS-PORTAL' || !!el.closest('nextjs-portal')
      }

      function visivel(el: Element): boolean {
        if (eDoNavegadorDeDesenvolvimento(el)) return false
        const estilo = getComputedStyle(el)
        if (estilo.visibility === 'hidden' || estilo.display === 'none') return false
        if (Number(estilo.opacity) === 0) return false
        const r = el.getBoundingClientRect()
        return r.width > 0 && r.height > 0
      }

      /** Identificação curta e humana do elemento, para caber no relatório. */
      function descrever(el: Element): string {
        const tag = el.tagName.toLowerCase()
        const texto = (el.textContent ?? '').replace(/\s+/g, ' ').trim().slice(0, 40)
        const rotulo = el.getAttribute('aria-label') ?? ''
        const tipo = el.getAttribute('type') ?? ''
        const partes = [tag]
        if (tipo) partes.push('[type=' + tipo + ']')
        if (texto) partes.push('"' + texto + '"')
        else if (rotulo) partes.push('(aria-label="' + rotulo + '")')
        return partes.join(' ')
      }

      /** Só o texto que é filho DIRETO — senão todo ancestral conta duas vezes. */
      function textoProprio(el: Element): string {
        let t = ''
        el.childNodes.forEach((n) => {
          if (n.nodeType === Node.TEXT_NODE) t += n.textContent ?? ''
        })
        return t.replace(/\s+/g, ' ').trim()
      }

      const larguraVisivel = document.documentElement.clientWidth
      const todos = Array.from(document.querySelectorAll('body *')).filter(visivel)
      const interativos = Array.from(document.querySelectorAll(SELETOR_INTERATIVO)).filter(visivel)

      // --- rolagem horizontal ------------------------------------------------
      const estourandoALargura: Achado[] = []
      todos.forEach((el) => {
        const r = el.getBoundingClientRect()
        // 1px de folga: arredondamento de subpixel não é defeito de layout.
        if (r.right > larguraVisivel + 1 || r.left < -1) {
          estourandoALargura.push({
            alvo: descrever(el),
            detalhe:
              'vai de ' +
              Math.round(r.left) +
              'px a ' +
              Math.round(r.right) +
              'px, e a tela tem ' +
              larguraVisivel +
              'px',
          })
        }
      })

      // --- tamanho de fonte --------------------------------------------------
      const textoMiudo: Achado[] = []
      todos.forEach((el) => {
        if (!textoProprio(el)) return
        const px = parseFloat(getComputedStyle(el).fontSize)
        if (px < fonteMinima) {
          textoMiudo.push({ alvo: descrever(el), detalhe: px + 'px' })
        }
      })

      // --- alvo de toque -----------------------------------------------------
      const alvoPequeno: Achado[] = []
      interativos.forEach((el) => {
        const r = el.getBoundingClientRect()
        if (r.width >= alvoMinimo && r.height >= alvoMinimo) return

        // Link corrido dentro de frase nunca terá 44px de altura. Continua
        // sendo achado — a régua não abre exceção —, mas vai marcado, para o
        // relatório não misturar isso com um botão pequeno de verdade.
        const emLinhaDeTexto =
          el.tagName === 'A' &&
          getComputedStyle(el).display.startsWith('inline') &&
          !!el.parentElement &&
          textoProprio(el.parentElement).length > 0

        alvoPequeno.push({
          alvo: descrever(el),
          detalhe:
            Math.round(r.width) +
            'x' +
            Math.round(r.height) +
            'px' +
            (emLinhaDeTexto ? ' — link em meio a texto' : ''),
        })
      })

      // --- ícone sem rótulo de texto ----------------------------------------
      // "Ícone nunca sozinho para ação importante: ícone + rótulo de TEXTO."
      // `aria-label` resolve o leitor de tela e não resolve quem enxerga e não
      // reconhece o desenho — que é o público do Princípio XII.
      const iconeSemRotulo: Achado[] = []
      interativos.forEach((el) => {
        if (!el.querySelector('svg, img')) return
        const texto = (el.textContent ?? '').replace(/\s+/g, ' ').trim()
        if (texto) return
        const rotulo = el.getAttribute('aria-label')
        iconeSemRotulo.push({
          alvo: descrever(el),
          detalhe: rotulo
            ? 'só ícone; tem aria-label ("' + rotulo + '") mas nenhum rótulo visível'
            : 'só ícone, sem rótulo visível e sem aria-label',
        })
      })

      // --- informação só por cor --------------------------------------------
      const informacaoSoPorCor: Achado[] = []

      document.querySelectorAll('[role="alert"], [role="status"]').forEach((el) => {
        if (!visivel(el)) return
        if (!(el.textContent ?? '').trim()) {
          informacaoSoPorCor.push({
            alvo: descrever(el),
            detalhe: 'faixa de aviso sem texto: a cor é a única portadora',
          })
        }
      })

      /*
       * WCAG 1.4.1 aplicada a link: se o link se separa do texto ao redor
       * APENAS por ser de outra cor, quem não distingue aquelas duas cores não
       * vê que ali há um link.
       *
       * A primeira versão desta asserção olhava só o `fontWeight` contra o do
       * pai e só entrava quando o pai tinha texto solto. A T008 mostrou que
       * isso era furado duas vezes: deixava passar link que é filho direto de
       * um contêiner (o pai não tem texto próprio, e a checagem nem rodava) e
       * aceitava 500 contra 400 como "peso próprio", que a olho nu não separa
       * nada. Os dois furos foram encontrados rodando contra as telas da spec
       * 001 — que é exatamente para isso que a T008 existe.
       *
       * O que vale como distinção que NÃO é cor de texto: sublinhado, borda,
       * fundo próprio (o link virou botão, e botão se reconhece pela forma) ou
       * diferença de peso de pelo menos 200, que é o salto que se enxerga.
       */
      const PESO_QUE_SE_ENXERGA = 200

      function corDoEntorno(el: Element): { cor: string; fundo: string } {
        let p = el.parentElement
        while (p && p !== document.body) {
          // O ancestral mais próximo que carrega texto que não é o do link.
          if (textoProprio(p) || (p.textContent ?? '').trim() !== (el.textContent ?? '').trim()) {
            const s = getComputedStyle(p)
            return { cor: s.color, fundo: s.backgroundColor }
          }
          p = p.parentElement
        }
        const s = getComputedStyle(document.body)
        return { cor: s.color, fundo: s.backgroundColor }
      }

      Array.from(document.querySelectorAll('a[href]'))
        .filter(visivel)
        .forEach((el) => {
          const meu = getComputedStyle(el)
          const entorno = corDoEntorno(el)

          // Mesma cor do entorno: seja o que for que o distinga, não é a cor.
          if (meu.color === entorno.cor) return

          const sublinhado = meu.textDecorationLine.includes('underline')
          const temBorda = ['Top', 'Right', 'Bottom', 'Left'].some(
            (lado) => parseFloat(meu.getPropertyValue('border-' + lado.toLowerCase() + '-width')) > 0,
          )
          const fundoProprio =
            meu.backgroundColor !== entorno.fundo &&
            meu.backgroundColor !== 'rgba(0, 0, 0, 0)' &&
            meu.backgroundColor !== 'transparent'
          const pesoDoEntorno = el.parentElement
            ? Number(getComputedStyle(el.parentElement).fontWeight)
            : 400
          const pesoDestaca =
            Math.abs(Number(meu.fontWeight) - pesoDoEntorno) >= PESO_QUE_SE_ENXERGA

          if (sublinhado || temBorda || fundoProprio || pesoDestaca) return

          informacaoSoPorCor.push({
            alvo: descrever(el),
            detalhe:
              'separa-se do texto ao redor só pela cor (' +
              meu.color +
              ' contra ' +
              entorno.cor +
              '), sem sublinhado, borda, fundo ou salto de peso — peso ' +
              meu.fontWeight +
              ' contra ' +
              pesoDoEntorno,
          })
        })

      return {
        larguraDoDocumento: document.documentElement.scrollWidth,
        larguraVisivel,
        estourandoALargura,
        fonteDoCorpoPx: parseFloat(getComputedStyle(document.body).fontSize),
        fonteDaRaizPx: parseFloat(getComputedStyle(document.documentElement).fontSize),
        textoMiudo,
        alvoPequeno,
        iconeSemRotulo,
        informacaoSoPorCor,
      }
    },
    { alvoMinimo: ALVO_MINIMO_PX, fonteMinima: FONTE_MINIMA_PX },
  )
}

/**
 * Foco visível, percorrido pelo TECLADO e não por `focus()`.
 *
 * A diferença não é estilística: `:focus-visible` — que é como praticamente
 * todo componente desenha o anel hoje — **não** casa com foco dado por script
 * na maioria dos casos. Um portão que usasse `el.focus()` acusaria "sem foco
 * visível" em tela correta, ou passaria a mão em tela errada, dependendo do
 * elemento. Tabular é o que o usuário de teclado faz.
 */
async function focosSemAnel(page: Page): Promise<Achado[]> {
  const achados: Achado[] = []
  const jaVistos = new Set<string>()

  for (let i = 0; i < MAXIMO_DE_PARADAS; i++) {
    await page.keyboard.press('Tab')

    const parada = await page.evaluate(() => {
      const el = document.activeElement
      if (!el || el === document.body || el === document.documentElement) return null
      // Sobreposição do `next dev`: ferramenta, não produto (ver `medir()`).
      // PULAR, e não parar: se ela fosse a primeira parada e o laço terminasse
      // aqui, o portão nunca chegaria a nenhum controle de verdade — e passaria
      // por não ter olhado, que é o pior jeito de passar.
      if (el.tagName === 'NEXTJS-PORTAL' || el.closest('nextjs-portal')) {
        return { pular: true } as const
      }

      const estilo = getComputedStyle(el)
      const larguraDoContorno = parseFloat(estilo.outlineWidth) || 0
      const temContorno = estilo.outlineStyle !== 'none' && larguraDoContorno > 0
      const temSombra = estilo.boxShadow !== 'none' && estilo.boxShadow !== ''

      const texto = (el.textContent ?? '').replace(/\s+/g, ' ').trim().slice(0, 40)
      return {
        pular: false as const,
        chave: el.tagName + ':' + texto + ':' + (el.getAttribute('type') ?? ''),
        alvo: el.tagName.toLowerCase() + (texto ? ' "' + texto + '"' : ''),
        temIndicador: temContorno || temSombra,
        contorno: 'outline: ' + estilo.outlineStyle + ' ' + estilo.outlineWidth,
        sombra: 'box-shadow: ' + estilo.boxShadow,
      }
    })

    if (!parada) break
    if (parada.pular) continue
    if (jaVistos.has(parada.chave)) break // deu a volta: o ciclo de foco fechou
    jaVistos.add(parada.chave)

    if (!parada.temIndicador) {
      achados.push({
        alvo: parada.alvo,
        detalhe:
          'sem contorno e sem sombra ao receber o foco (' +
          parada.contorno +
          '; ' +
          parada.sombra +
          ')',
      })
    }
  }

  return achados
}

function listar(achados: Achado[]): string {
  return achados.map((a) => '    · ' + a.alvo + ' — ' + a.detalhe).join('\n')
}

for (const tela of TELAS) {
  test('portão — ' + tela.nome, async ({ page }, info) => {
    const comFonteAmpliada = info.project.name === PROJETO_FONTE_AMPLIADA

    if (comFonteAmpliada) await aplicarFonteDoSistemaAmpliada(page)
    await tela.preparar?.(page)
    await page.goto(tela.rota)
    // A medição é de layout assentado, não de quadro intermediário.
    await page.waitForLoadState('networkidle')

    const m = await medir(page)

    /*
     * Com fonte do sistema ampliada, a PRIMEIRA pergunta não é se a tela
     * aguentou — é se a tela chegou a receber o pedido. Página que fixa o
     * tamanho da raiz em pixel ignora a configuração do aparelho, e aí todo o
     * resto da medição seria sobre um cenário que não aconteceu. O
     * `ux-requirements.md` exige o respeito a esse ajuste em letra própria:
     * "a interface respeita o ajuste de tamanho de fonte do aparelho/navegador".
     */
    if (comFonteAmpliada) {
      expect
        .soft(
          m.fonteDaRaizPx,
          'a tela IGNOROU a fonte do sistema ampliada: a raiz continua em ' +
            m.fonteDaRaizPx +
            'px quando o navegador pediu ' +
            FONTE_AMPLIADA_PX +
            'px. Ou algo fixa o tamanho da raiz em pixel, ou o cenário não foi ' +
            'aplicado — e nos dois casos as asserções abaixo NÃO significam que ' +
            'a tela passou com fonte ampliada',
        )
        .toBeGreaterThanOrEqual(FONTE_AMPLIADA_PX)
    }

    expect
      .soft(
        m.larguraDoDocumento,
        'a página tem rolagem horizontal a ' + m.larguraVisivel + 'px de largura',
      )
      .toBeLessThanOrEqual(m.larguraVisivel + 1)

    expect
      .soft(
        m.estourandoALargura,
        'elementos passam da largura da tela:\n' + listar(m.estourandoALargura),
      )
      .toEqual([])

    expect
      .soft(m.fonteDoCorpoPx, 'a fonte base do corpo é ' + m.fonteDoCorpoPx + 'px')
      .toBeGreaterThanOrEqual(FONTE_MINIMA_PX)

    expect
      .soft(
        m.textoMiudo,
        'texto visível abaixo de ' + FONTE_MINIMA_PX + 'px:\n' + listar(m.textoMiudo),
      )
      .toEqual([])

    expect
      .soft(
        m.alvoPequeno,
        'alvos de toque menores que ' +
          ALVO_MINIMO_PX +
          'x' +
          ALVO_MINIMO_PX +
          'px:\n' +
          listar(m.alvoPequeno),
      )
      .toEqual([])

    expect
      .soft(m.iconeSemRotulo, 'ícone sem rótulo de texto:\n' + listar(m.iconeSemRotulo))
      .toEqual([])

    expect
      .soft(
        m.informacaoSoPorCor,
        'informação transmitida só por cor:\n' + listar(m.informacaoSoPorCor),
      )
      .toEqual([])

    const semAnel = await focosSemAnel(page)
    expect.soft(semAnel, 'foco sem indicação visível:\n' + listar(semAnel)).toEqual([])

    const axe = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      // Sobreposição do `next dev`: ferramenta, não produto. Ver `medir()`.
      .exclude('nextjs-portal')
      .analyze()

    const violacoes: Achado[] = axe.violations.map((v) => ({
      alvo: v.id + ' (' + v.impact + ')',
      detalhe:
        v.help +
        ' — ' +
        v.nodes.length +
        ' ocorrência(s):\n' +
        v.nodes
          .slice(0, 5)
          .map(
            (n) =>
              '        ' +
              n.target.join(' ') +
              '\n          ' +
              // O resumo traz os números (razão medida, razão exigida, as duas
              // cores). Sem isso o relatório diz "contraste ruim" e ninguém
              // sabe o quanto nem entre o quê.
              (n.failureSummary ?? '').replace(/\n/g, '\n          '),
          )
          .join('\n'),
    }))

    expect.soft(violacoes, 'axe acusou violação de WCAG:\n' + listar(violacoes)).toEqual([])
  })
}
