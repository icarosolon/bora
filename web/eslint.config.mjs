import { defineConfig, globalIgnores } from "eslint/config";
import nextVitals from "eslint-config-next/core-web-vitals";
import nextTs from "eslint-config-next/typescript";

// D22 (docs/product/design-system.md): nenhuma tela escreve cor literal, só token de papel.
// Cor literal = cor da paleta padrão do Tailwind (zinc-50, emerald-600, black, white...) com
// qualquer utilitário ou variante, cor arbitrária ([#fff], [rgb(...)]) e hex/função de cor em
// `style`. A paleta padrão acessada por variável CSS também é cor literal (decisão do Ícaro,
// 2026-10-14): var(--color-zinc-500), bg-(--color-zinc-500), var(--color-black), em classe e
// em `style`. Token de papel (bg-primary, bg-(--primary), var(--primary)...) passa.
const UTILITIES =
  "bg|text|border|border-[xytblrse]|ring|ring-offset|outline|fill|stroke|from|via|to|divide|" +
  "placeholder|caret|accent|decoration|shadow|inset-shadow|inset-ring|drop-shadow|text-shadow";
const PALETTE =
  "red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|" +
  "fuchsia|pink|rose|slate|gray|zinc|neutral|stone|taupe|mauve|mist|olive";
// Fragmento único da cor nomeada, compartilhado por todos os padrões para não se desencontrarem.
const COLOR_NAME = String.raw`(?:black|white|(?:${PALETTE})-\d{2,3})`;
// color-mix() fica de fora de propósito: com var(--token) nos argumentos é cor por token.
// Se levar hex, rgb()/hsl(), cor da paleta ou variável da paleta, o padrão correspondente o acusa.
const COLOR_FN = "rgba?|hsla?|hwb|lab|lch|oklab|oklch";
// Dentro de colchetes do Tailwind o "_" é o separador de espaço: não é parte de palavra.
const BEFORE = String.raw`(?<![A-Za-z0-9-])`;
const AFTER = String.raw`(?![A-Za-z0-9-])`;
const HEX = String.raw`#[0-9a-fA-F]{3,8}${AFTER}`;
const COLOR_FN_CALL = String.raw`${BEFORE}(?:${COLOR_FN})\(`;
const NAMED_COLOR = String.raw`${BEFORE}${COLOR_NAME}${AFTER}`;
const PALETTE_VAR = String.raw`${BEFORE}--color-${COLOR_NAME}${AFTER}`;

// Classe fora de colchetes: utilitário de cor + cor nomeada.
const NAMED_COLOR_CLASS = String.raw`(?<![\w-])(?:${UTILITIES})-${COLOR_NAME}(?![\w-])`;
// Cor arbitrária: utilitário de cor + [...] ou (...) com hex, função de cor, cor nomeada ou
// variável da paleta dentro.
const ARBITRARY_COLOR_CLASS =
  String.raw`(?<![\w-])(?:${UTILITIES})-(?:\[[^\]]*|\([^)]*)(?:${HEX}|${COLOR_FN_CALL}|${NAMED_COLOR}|${PALETTE_VAR})`;
const STYLE_COLOR = `(?:${HEX}|${COLOR_FN_CALL}|${PALETTE_VAR})`;

const MESSAGE_CLASS =
  "Cor literal em classe é proibida (D22). Use um token de papel (bg-primary, " +
  "text-muted-foreground etc.), definido em globals.css.";
const MESSAGE_STYLE =
  "Cor literal em style é proibida (D22). Use um token de papel via var(--...).";

const isString = (regex) => [`Literal[value=/${regex}/]`, `TemplateElement[value.raw=/${regex}/]`];

// ATENÇÃO: no-restricted-syntax é UMA regra só (as da eslint-config-next não a definem).
// Por isso o "off" dos blocos de exceção, abaixo, desliga a regra INTEIRA nesses dois arquivos,
// e qualquer outro bloco que redefina no-restricted-syntax substitui estes seletores.
const noLiteralColorRule = [
  "error",
  ...isString(`${NAMED_COLOR_CLASS}|${ARBITRARY_COLOR_CLASS}`).map((selector) => ({
    selector,
    message: MESSAGE_CLASS,
  })),
  ...[`JSXAttribute[name.name='style'] Literal[value=/${STYLE_COLOR}/]`,
    `JSXAttribute[name.name='style'] TemplateElement[value.raw=/${STYLE_COLOR}/]`].map(
    (selector) => ({ selector, message: MESSAGE_STYLE }),
  ),
];

const eslintConfig = defineConfig([
  ...nextVitals,
  ...nextTs,
  {
    files: ["src/**/*.tsx"],
    rules: { "no-restricted-syntax": noLiteralColorRule },
  },
  // Exceções nomeadas da regra de cor literal (decisão do Ícaro, 2026-10-14, opção c).
  // Caminhos exatos, sem glob largo. Cada exceção sai junto com a tarefa que corrige o arquivo.
  {
    // Remover quando a T021 trocar o boilerplate do create-next-app (spec 002).
    files: ["src/app/page.tsx"],
    rules: { "no-restricted-syntax": "off" },
  },
  {
    // Remover quando a T013 trocar o emerald-600 por token de papel (spec 002).
    files: ["src/components/ui/alert.tsx"],
    rules: { "no-restricted-syntax": "off" },
  },
  // Override default ignores of eslint-config-next.
  globalIgnores([
    // Default ignores of eslint-config-next:
    ".next/**",
    "out/**",
    "build/**",
    "next-env.d.ts",
  ]),
]);

export default eslintConfig;
