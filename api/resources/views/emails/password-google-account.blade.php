{{-- Pedido de redefinicao numa conta que so entra pelo provedor externo (US4-4).

     Este e-mail existe porque a resposta da API e NEUTRA de proposito: ela nao
     revela se a conta existe nem como ela entra. Sem esta mensagem, a pessoa
     ficaria esperando um link que nunca viria. --}}
<p>Oi, {{ $name }}!</p>

<p>Recebemos um pedido para redefinir a senha da sua conta no Bora. Acontece que
a sua conta entra com o {{ $provider }} e ainda nao tem senha — por isso nao ha
o que redefinir.</p>

<p><a href="{{ $url }}">Entrar com o {{ $provider }}</a></p>

<p>Se quiser tambem poder entrar com e-mail e senha, e so definir uma senha
depois de entrar.</p>

<p>Se nao foi voce que pediu, ignore este e-mail: nada muda na sua conta.</p>

<p>— Equipe Bora</p>
