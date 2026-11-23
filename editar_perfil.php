<?php
// EDITAR PERFIL
require_once 'config.php';
exigirLogin();

$tituloPagina = "Editar Perfil - Conecta";
$meuId = $_SESSION['usuario_id'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'];

    //  Nome e legenda 
    if ($acao === 'dados') {
        $nome = trim($_POST['nome']);
        $bio = trim($_POST['bio']);
        if (mb_strlen($nome) < 2 || mb_strlen($nome) > 60) {
            $erro = 'O nome deve ter entre 2 e 60 caracteres.';
        } elseif (mb_strlen($bio) > 150) {
            $erro = 'A legenda deve ter no máximo 150 caracteres.';
        } else {
            supabaseRequest("usuarios?id=eq.$meuId", 'PATCH', ['nome' => $nome, 'bio' => $bio]);
            $_SESSION['usuario_nome'] = $nome;
            registrarLog("Perfil Atualizado");
            flash('success', 'Perfil atualizado!');
        }
    }

    //  Foto: salva a nova e apaga a antiga do disco 
    if ($acao === 'foto') {
        $novaFoto = salvarFoto($_FILES['foto'], $erro);
        if ($novaFoto) {
            $antiga = supabaseUm("usuarios?id=eq.$meuId&select=foto")['foto'];
            supabaseRequest("usuarios?id=eq.$meuId", 'PATCH', ['foto' => $novaFoto]);
            if ($antiga !== 'default.png') {
                @unlink(__DIR__ . '/uploads/' . $antiga);
            }
            registrarLog("Foto Alterada");
            flash('success', 'Foto de perfil atualizada!');
        }
    }

    //  Senha 
    if ($acao === 'senha') {
        $usuario = supabaseUm("usuarios?id=eq.$meuId&select=senha");
        if (!password_verify($_POST['senha_atual'], $usuario['senha'])) {
            $erro = 'Senha atual incorreta.';
        } elseif (strlen($_POST['senha_nova']) < 6) {
            $erro = 'A nova senha deve ter no mínimo 6 caracteres.';
        } elseif ($_POST['senha_nova'] !== $_POST['senha_nova2']) {
            $erro = 'As novas senhas não conferem.';
        } else {
            supabaseRequest("usuarios?id=eq.$meuId", 'PATCH', ['senha' => password_hash($_POST['senha_nova'], PASSWORD_DEFAULT)]);
            registrarLog("Senha Alterada");
            flash('success', 'Senha alterada com sucesso!');
        }
    }

    if (!$erro) {
        redirecionar('editar_perfil.php');
    }
}

$eu = supabaseUm("usuarios?id=eq.$meuId&select=nome,bio,foto");

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <?= pegarFlash() ?>
        <?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Editar perfil</h1>
            <a href="<?= linkDetalhe('usuario', $meuId) ?>" class="btn btn-outline-secondary btn-sm">Ver meu perfil</a>
        </div>

        <div class="row g-4">
            <!-- Nome e legenda -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">Dados do perfil</h2>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome *</label>
                                <input type="text" id="nome" name="nome" class="form-control" minlength="2" maxlength="60" value="<?= e($eu['nome']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="bio" class="form-label">Legenda do perfil</label>
                                <input type="text" id="bio" name="bio" class="form-control" maxlength="150" value="<?= e($eu['bio']) ?>" data-contador>
                            </div>
                            <button name="acao" value="dados" class="btn btn-primary">Salvar</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Foto -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">Foto de perfil</h2>
                        <div class="text-center mb-3">
                            <img src="uploads/<?= e($eu['foto']) ?>" class="rounded-circle border" width="110" height="110" style="object-fit: cover;">
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="file" name="foto" class="form-control mb-1" accept="image/png, image/jpeg, image/gif" data-max-mb="2" required>
                            <div class="form-text mb-3">JPG, PNG ou GIF de até 2 MB.</div>
                            <button name="acao" value="foto" class="btn btn-primary">Trocar foto</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Senha -->
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h5">Trocar senha</h2>
                <form method="POST" class="row g-2">
                    <div class="col-md-4">
                        <input type="password" name="senha_atual" class="form-control" placeholder="Senha atual" required>
                    </div>
                    <div class="col-md-3">
                        <input type="password" id="senha_nova" name="senha_nova" class="form-control" placeholder="Nova senha (mín. 6)" minlength="6" required>
                    </div>
                    <div class="col-md-3">
                        <input type="password" name="senha_nova2" class="form-control" placeholder="Confirmar nova" data-confirma="senha_nova" required>
                    </div>
                    <div class="col-md-2">
                        <button name="acao" value="senha" class="btn btn-outline-primary w-100">Alterar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
