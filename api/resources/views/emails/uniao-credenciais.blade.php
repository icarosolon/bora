{{-- Uniao de credenciais (US3, plano B da decisao D1). --}}
<p>Oi, {{ $nome }}!</p>

<p>Voce tentou entrar no Bora com o Google, e ja existe uma conta sua com esse
mesmo e-mail. Para unir as duas formas de entrar, confirme aqui:</p>

<p><a href="{{ $url }}">Unir e entrar</a></p>

<p>Se o botao nao funcionar, copie e cole este endereco no navegador:<br>
{{ $url }}</p>

<p>Depois disso voce podera entrar com o Google ou com sua senha, como preferir.
O link vale por 1 hora.</p>

<p>Se nao foi voce que tentou entrar, ignore este e-mail: nada muda na sua conta.</p>

<p>— Equipe Bora</p>
