<?php
require_once 'config.php';
$tituloPagina = "FAQ e Fale Conosco - Conecta";
$descricaoPagina = "Perguntas frequentes sobre o Conecta e formulário de contato.";
$aviso = '';
$erros = [];

// Perguntas frequentes
$faq = [
    ['O que é o meu código #Tag?', 'É o seu identificador (ex.: #4829), gerado no cadastro. Passe esse código para seus amigos encontrarem você sem precisar do seu e-mail.'],
    ['Como adicionar um contato?', 'Em "Buscar #Tag", digite o código da pessoa e clique em "Solicitar contato". Quando ela aceitar em "Pedidos", vocês aparecem nos contatos um do outro.'],
    ['Posso cancelar um pedido que enviei?', 'Sim. Em "Pedidos", na seção "Enviados", clique em "Cancelar".'],
    ['Como funciona o chat?', 'Em "Meus Contatos", clique em "Enviar mensagem". As mensagens aparecem na hora para os dois, sem recarregar a página.'],
    ['O que é o @gemini?', 'É a Inteligência Artificial do chat. Comece a mensagem com @gemini e a pergunta (ex.: "@gemini o que é PHP?") e a resposta aparece na conversa.'],
    ['Como troco minha foto e minha legenda?', 'Em "Meu Perfil", clique em "Editar perfil".'],
    ['Esqueci minha senha. E agora?', 'Na tela de login, clique em "Esqueci minha senha" e informe seu e-mail. Você receberá uma senha provisória.'],
];

// Os assuntos vêm do banco (tabela assuntos_contato)
$assuntos = supabaseLista('assuntos_contato?select=id,nome,outro&order=ordem.asc');

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');
$assuntoId = (int) ($_POST['assunto_id'] ?? 0);
$assuntoOutro = trim($_POST['assunto_outro'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assunto = null;
    foreach ($assuntos as $a) {
        if ($a['id'] === $assuntoId) {
            $assunto = $a;
        }
    }

    // Validação 
    if (!$assunto) {
        $erros[] = 'Selecione um assunto.';
    } elseif ($assunto['outro'] && mb_strlen($assuntoOutro) < 3) {
        $erros[] = 'Descreva o assunto.';
    }
    if (mb_strlen($nome) < 2) {
        $erros[] = 'Informe seu nome.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (mb_strlen($mensagem) < 10) {
        $erros[] = 'A mensagem deve ter pelo menos 10 caracteres.';
    }

    if (!$erros) {
        // Grava no banco
        supabaseRequest('contatos_site', 'POST', [
            'assunto_id' => $assuntoId,
            'assunto_outro' => $assunto['outro'] ? $assuntoOutro : null,
            'nome' => $nome,
            'email' => $email,
            'mensagem' => $mensagem,
        ]);

        // Manda um e-mail para o dono do sistema 
        $textoAssunto = $assunto['outro'] ? $assuntoOutro : $assunto['nome'];
        $corpo = '<h2>Nova mensagem pelo Fale Conosco</h2>'
            . '<p><strong>Assunto:</strong> ' . e($textoAssunto) . '<br><strong>Nome:</strong> ' . e($nome) . '<br><strong>E-mail:</strong> ' . e($email) . '</p>'
            . '<p>' . nl2br(e($mensagem)) . '</p>';
        enviarEmailSMTP(EMAIL_DONO, "Contato Conecta: $textoAssunto", $corpo, $email);

        registrarLog("Fale Conosco Enviado");
        $aviso = '<div class="alert alert-success">Mensagem enviada! Responderemos no e-mail informado.</div>';
        $nome = $email = $mensagem = $assuntoOutro = '';
        $assuntoId = 0;
    }
}

include 'header.php';
?>

<div class="row">
    <!-- FAQ -->
    <div class="col-md-7 mb-4">
        <h1 class="h2">Como funciona o Conecta?</h1>
        <div class="accordion" id="faq">
            <?php foreach ($faq as $i => [$pergunta, $resposta]): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q<?= $i ?>">
                            <?= e($pergunta) ?>
                        </button>
                    </h2>
                    <div id="q<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#faq">
                        <div class="accordion-body"><?= e($resposta) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Fale Conosco -->
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h4 mb-3">Fale Conosco</h2>
                <?= $aviso ?>
                <?php if ($erros): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($erros as $erro): ?><div><?= e($erro) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Seu nome *</label>
                        <input type="text" id="nome" name="nome" class="form-control" minlength="2" maxlength="100" value="<?= e($nome) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Seu e-mail *</label>
                        <input type="email" id="email" name="email" class="form-control" maxlength="150" value="<?= e($email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="assunto_id" class="form-label">Assunto *</label>
                        <select id="assunto_id" name="assunto_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($assuntos as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $a['outro'] ? 'data-outro' : '' ?> <?= $assuntoId === $a['id'] ? 'selected' : '' ?>><?= e($a['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Aparece só quando o assunto é "Outro" -->
                    <div class="mb-3 d-none" id="campoOutro">
                        <label for="assunto_outro" class="form-label">Qual o assunto? *</label>
                        <input type="text" id="assunto_outro" name="assunto_outro" class="form-control" minlength="3" maxlength="100" value="<?= e($assuntoOutro) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="mensagem" class="form-label">Mensagem *</label>
                        <textarea id="mensagem" name="mensagem" rows="4" class="form-control" minlength="10" maxlength="2000" data-contador required><?= e($mensagem) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Enviar mensagem</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
