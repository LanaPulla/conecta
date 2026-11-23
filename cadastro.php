<?php
require_once 'config.php';
$tituloPagina = "Cadastro - Conecta";
$descricaoPagina = "Crie sua conta no Conecta, receba seu código #Tag e comece a conversar com seus amigos.";
$erros = [];
$sucesso = '';

if (isset($_SESSION['usuario_id'])) {
    redirecionar('contatos.php');
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$bio = trim($_POST['bio'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'];

    // validações back-end 
    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 60) {
        $erros[] = 'O nome deve ter entre 2 e 60 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    } elseif (supabaseUm('usuarios?email=eq.' . urlencode($email) . '&select=id')) {
        $erros[] = 'Este e-mail já está cadastrado.';
    }
    if (strlen($senha) < 6) {
        $erros[] = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $_POST['senha2']) {
        $erros[] = 'As senhas não conferem.';
    }
    if (mb_strlen($bio) > 150) {
        $erros[] = 'A legenda deve ter no máximo 150 caracteres.';
    }
    if (!isset($_POST['aceite_termos'])) {
        $erros[] = 'Você precisa aceitar os Termos de Uso.';
    }

    // foto 
    $foto = 'default.png';
    if (!$erros && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $foto = salvarFoto($_FILES['foto'], $erroFoto);
        if (!$foto) {
            $erros[] = $erroFoto;
        }
    }

    if (!$erros) {
        // Sorteia um código #1234 enquanto a tag já pertencer a alguém
        do {
            $codigoTag = '#' . random_int(1000, 9999);
        } while (supabaseUm('usuarios?codigo_tag=eq.' . urlencode($codigoTag) . '&select=id'));

        supabaseRequest('usuarios', 'POST', [
            'nome' => $nome,
            'codigo_tag' => $codigoTag,
            'email' => $email,
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
            'bio' => $bio ?: 'Olá! Estou usando o Conecta.',
            'foto' => $foto,
        ]);

        registrarLog("Cadastro", "Nova conta: $email");
        $sucesso = "Conta criada! Seu código é <span class='badge bg-dark fs-6'>$codigoTag</span>. <a href='index.php' class='alert-link'>Clique aqui para entrar</a>.";
        $nome = $email = $bio = '';
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 text-center mb-3">Criar Nova Conta</h1>
                <p class="text-muted text-center small mb-4">Você recebe um código #Tag para que outras pessoas encontrem você.</p>

                <?php if ($erros): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($erros as $erro): ?><div><?= e($erro) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($sucesso): ?><div class="alert alert-success"><?= $sucesso ?></div><?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" id="nome" name="nome" class="form-control" minlength="2" maxlength="60" value="<?= e($nome) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail *</label>
                        <input type="email" id="email" name="email" class="form-control" maxlength="150" value="<?= e($email) ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label for="senha" class="form-label">Senha (mín. 6) *</label>
                            <input type="password" id="senha" name="senha" class="form-control" minlength="6" required>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label for="senha2" class="form-label">Confirmar senha *</label>
                            <input type="password" id="senha2" name="senha2" class="form-control" data-confirma="senha" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="bio" class="form-label">Legenda do perfil</label>
                        <input type="text" id="bio" name="bio" class="form-control" maxlength="150" value="<?= e($bio) ?>" data-contador>
                    </div>
                    <div class="mb-3">
                        <label for="foto" class="form-label">Foto de perfil (opcional, até 2 MB)</label>
                        <input type="file" id="foto" name="foto" class="form-control" accept="image/png, image/jpeg, image/gif" data-max-mb="2">
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="aceite_termos" name="aceite_termos" required>
                        <label class="form-check-label small" for="aceite_termos">
                            Li e aceito os <a href="termos.php" target="_blank">Termos de Uso</a>. *
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Finalizar cadastro</button>
                </form>

                <hr>
                <div class="text-center small"><a href="index.php">Já possui uma conta? Faça login</a></div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
