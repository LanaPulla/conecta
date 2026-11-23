<?php
require_once 'config.php';
$tituloPagina = "Login - Conecta";
$descricaoPagina = "Entre no Conecta com seu e-mail e senha para conversar em tempo real com seus contatos.";
$msg = '';

if (isset($_SESSION['usuario_id'])) {
    redirecionar('contatos.php');
}

$acao = $_POST['acao'] ?? '';

// Login
if ($acao === 'login') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $usuario = supabaseUm('usuarios?email=eq.' . urlencode($email) . '&select=id,nome,email,senha,codigo_tag,foto');

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_tag'] = $usuario['codigo_tag'];
        registrarLog("Login");
        redirecionar('contatos.php');
    }

    registrarLog("Login Recusado", "E-mail: $email");
    $msg = '<div class="alert alert-danger">E-mail ou senha incorretos!</div>';
}

// Recuperação de senha
if ($acao === 'recuperar') {
    $email = trim($_POST['email_recuperacao']);
    $usuario = supabaseUm('usuarios?email=eq.' . urlencode($email) . '&select=id');

    if ($usuario) {
        $novaSenha = substr(bin2hex(random_bytes(4)), 0, 8);
        $corpo = "<h2>Recuperação de Acesso</h2>
                  <p>Sua nova senha provisória do <strong>Conecta</strong> é: <strong>$novaSenha</strong></p>
                  <p>Entre com ela e, se quiser, troque em Meu Perfil &rarr; Editar perfil.</p>";

        // Verificação se foi enviado email para a atualização de senha temporária 
        if (enviarEmailSMTP($email, "Recuperação de Senha - Conecta", $corpo)) {
            supabaseRequest('usuarios?id=eq.' . $usuario['id'], 'PATCH', ['senha' => password_hash($novaSenha, PASSWORD_DEFAULT)]);
        }
    }
    registrarLog("Pediu Nova Senha", "E-mail: $email");

    $msg = '<div class="alert alert-info">Se o e-mail <strong>' . e($email) . '</strong> estiver cadastrado, você receberá uma senha provisória. Verifique também o spam.</div>';
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <?= $msg ?>
        <?= pegarFlash() ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 text-center mb-4">Entrar no Conecta</h1>

                <form method="POST">
                    <input type="hidden" name="acao" value="login">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" id="senha" name="senha" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>

                <hr>
                <div class="d-flex justify-content-between small">
                    <a href="cadastro.php">Não tem conta? Cadastre-se</a>
                    <a href="#" class="text-secondary" data-bs-toggle="modal" data-bs-target="#modalRecuperar">Esqueci minha senha</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- recuperação de senha -->
<div class="modal fade" id="modalRecuperar" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="acao" value="recuperar">
            <div class="modal-header">
                <h5 class="modal-title">Recuperar Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="email_recuperacao" class="form-label">E-mail cadastrado</label>
                <input type="email" id="email_recuperacao" name="email_recuperacao" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Enviar nova senha</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
