{{-- Verificacao de e-mail (D5). Texto simples, linguagem do dia a dia. --}}
<p>Oi, {{ $nome }}!</p>

<p>Confirme seu e-mail para a gente saber que ele e seu mesmo:</p>

<p><a href="{{ $url }}">Confirmar meu e-mail</a></p>

<p>Se o botao nao funcionar, copie e cole este endereco no navegador:<br>
{{ $url }}</p>

<p>O link vale por 7 dias. Voce ja pode usar o Bora normalmente enquanto isso.</p>

<p>Se nao foi voce que criou a conta, e so ignorar este e-mail.</p>

<p>— Equipe Bora</p>
