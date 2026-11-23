<?php
require_once 'config.php';
$tituloPagina = "Termos de Uso - Conecta";
$descricaoPagina = "Termos de Uso, Privacidade e Plágio do Conecta, trabalho acadêmico de Programação Web II.";
include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-3">Termos de Uso</h1>

                <p>O <strong>Conecta</strong> é um <strong>trabalho acadêmico</strong> da disciplina de Programação Web II, feito apenas para fins de estudo. O sistema pode ficar fora do ar ou ser desativado a qualquer momento.</p>

                <h2 class="h5 mt-4">Uso</h2>
                <ul>
                    <li>Use dados verdadeiros e mantenha sua senha em sigilo.</li>
                    <li>Não envie conteúdo ofensivo, ilegal ou de outras pessoas sem autorização.</li>
                    <li>Contas que desrespeitarem essas regras podem ser removidas.</li>
                </ul>

                <h2 class="h5 mt-4">Privacidade</h2>
                <p>Guardamos apenas o necessário para o chat funcionar: nome, e-mail, senha (criptografada), foto, contatos e mensagens. Seu e-mail não aparece para outros usuários e seus dados não são vendidos nem compartilhados para publicidade. Os textos enviados com <code>@gemini</code> são processados pelo Google Gemini.</p>

                <p class="mt-4 mb-0 small text-muted">Dúvidas ou pedidos de exclusão de conta: <a href="faq_contato.php">Fale Conosco</a>.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
