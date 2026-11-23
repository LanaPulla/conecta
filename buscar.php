<?php

// get-one dinâmico

require_once 'config.php';
exigirLogin();

$tituloPagina = "Buscar Contatos - Conecta";
$meuId = $_SESSION['usuario_id'];
$hashtag = trim($_GET['tag'] ?? '');
$usuario = null;
$erro = '';

if ($hashtag !== '') {
    // Aceita "1234" ou "#1234"
    $hashtag = '#' . ltrim($hashtag, '#');

    if (!preg_match('/^#\d{4}$/', $hashtag)) {
        $erro = 'Código inválido. Use o formato #1234.';
    } else {
        // O código é único, então existe no máximo 1 usuário
        $usuario = supabaseUm('usuarios?codigo_tag=eq.' . urlencode($hashtag) . '&select=id,nome,codigo_tag,foto,bio');
        if ($usuario) {
            $relacao = relacaoCom($meuId, $usuario['id']);
        } else {
            $erro = "Nenhum usuário encontrado com o código $hashtag.";
        }
    }
    registrarLog("Buscou Contato", $hashtag);
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h1 class="h4">Buscar usuário por código</h1>
                <p class="text-muted">Seu código é: <span class="badge bg-primary"><?= e($_SESSION['usuario_tag']) ?></span></p>
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="tag" class="form-control" placeholder="Ex: #4829" value="<?= e($hashtag) ?>"
                        pattern="#?\d{4}" title="Use o formato #1234 (4 números)." required>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </form>
            </div>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-warning"><?= e($erro) ?></div>
        <?php elseif ($usuario): ?>
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div class="d-flex align-items-center gap-3">
                        <img src="uploads/<?= e($usuario['foto']) ?>" class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                        <div>
                            <h3 class="h6 mb-0"><?= e($usuario['nome']) ?> <small class="text-muted"><?= e($usuario['codigo_tag']) ?></small></h3>
                            <small class="text-muted"><?= e($usuario['bio']) ?></small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <?php if ($relacao === 'contato'): ?>
                            <span class="badge bg-success">Já é contato</span>
                        <?php elseif ($relacao === 'pedido_enviado' || $relacao === 'pedido_recebido'): ?>
                            <span class="badge bg-warning text-dark">Pedido pendente</span>
                        <?php elseif ($relacao === 'nenhuma'): ?>
                            <form method="POST" action="<?= linkDetalhe('usuario', $usuario['id']) ?>">
                                <button type="submit" name="acao" value="solicitar" class="btn btn-primary btn-sm">Solicitar contato</button>
                            </form>
                        <?php endif; ?>
                        <a href="<?= linkDetalhe('usuario', $usuario['id']) ?>" class="btn btn-outline-primary btn-sm">Ver perfil</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
