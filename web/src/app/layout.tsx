import type { Metadata } from 'next'
import { Geist, Geist_Mono } from 'next/font/google'
import { CabecalhoConta } from '@/components/auth/CabecalhoConta'
import './globals.css'

const geistSans = Geist({
  variable: '--font-geist-sans',
  subsets: ['latin'],
})

const geistMono = Geist_Mono({
  variable: '--font-geist-mono',
  subsets: ['latin'],
})

export const metadata: Metadata = {
  title: 'Bora — onde tem rolê hoje',
  description:
    'Descubra os rolês com música ao vivo perto de você. Bares, restaurantes e artistas em um só lugar.',
}

export default function RootLayout({ children }: LayoutProps<'/'>) {
  return (
    // lang="pt-BR" para o leitor de tela anunciar o idioma certo
    // (ux-requirements.md, acessibilidade técnica).
    <html
      lang="pt-BR"
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      <body className="min-h-full flex flex-col">
        <CabecalhoConta />
        {children}
      </body>
    </html>
  )
}
