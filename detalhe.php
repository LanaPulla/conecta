<?php

// get-one dinâmico

require_once 'config.php';
exigirLogin();

$meuId = $_SESSION['usuario_id'];
$tipo = $_GET['tipo'] ?? '';
$id = $_GET['id'] ?? '';
$registro = null;
$relacao = '';
$pedidoId = null; 

// Busca o registro 
if ($tipo === 'usuario' && uuidValido($id)) {
    $registro = supabaseUm("usuarios?id=eq.$id&select=id,nome,codigo_tag,bio,foto");
    $tituloPagina = 'Perfil de ' . ($registro['nome'] ?? '') . ' - Conecta';
} elseif ($tipo === 'pedido' && ctype_digit($id)) {
    $registro = supabaseUm("pedidos_contato?id=eq.$id&select=id,status,criado_em,respondido_em,solicitante_id,destinatario_id,"
        . "solicitante:usuarios!pedidos_contato_solicitante_id_fkey(id,nome,codigo_tag,foto),"
        . "destinatario:usuarios!pedidos_contato_destinatario_id_fkey(id,nome,codigo_tag,foto)");
    // Um pedido só pode ser visto por quem enviou ou recebeu
    if ($registro && $meuId !== $registro['solicitante_id'] && $meuId !== $registro['destinatario_id']) {
        $registro = null;
    }
    $tituloPagina = "Pedido de contato #$id - Conecta";
}

// não achou página 404 
if (!$registro) {
    http_response_code(404);
    $tituloPagina = 'Não encontrado - Conecta';
    include 'header.php';
    echo '<div class="text-center py-5"><h1>404</h1><p class="text-muted">Registro não encontrado.</p>'
        . '<a href="contatos.php" class="btn btn-primary">Voltar</a></div>';
    include 'footer.php';
    exit;
}

// enviar pedido de contato 
if ($tipo === 'usuario') {
    $relacao = relacaoCom($meuId, $registro['id'], $pedido);
    $pedidoId = $pedido['id'] ?? null;

    if (($_POST['acao'] ?? '') === 'solicitar') {
        if ($relacao === 'nenhuma') {
            supabaseRequest('pedidos_contato', 'POST', ['solicitante_id' => $meuId, 'destinatario_id' => $registro['id']]);
            registrarLog("Pedido de Contato Enviado");
            flash('success', "Pedido enviado! Quando {$registro['nome']} aceitar, vocês viram contatos.");
        } else {
            flash('warning', 'Já existe um pedido pendente ou vocês já são contatos.');
        }
        redirecionar(linkDetalhe('usuario', $registro['id']));
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <?= pegarFlash() ?>

        <?php if ($tipo === 'usuario'): ?>
            <!-- ===== USUÁRIO ===== -->
            <div class="card shadow text-center p-4">
                <img src="uploads/<?= e($registro['foto']) ?>" class="rounded-circle mx-auto mb-3 border" width="130" height="130" style="object-fit: cover;">
                <h1 class="h3"><?= e($registro['nome']) ?></h1>
                <p class="text-primary fw-bold mb-1"><?= e($registro['codigo_tag']) ?></p>
                <p class="text-muted"><?= e($registro['bio']) ?></p>
                <hr>

                <?php if ($relacao === 'eu'): ?>
                    <a href="editar_perfil.php" class="btn btn-outline-primary w-100">Editar perfil</a>

                <?php elseif ($relacao === 'contato'): ?>
                    <a href="<?= linkChat($registro['id']) ?>" class="btn btn-success w-100 mb-2">Enviar mensagem</a>
                    <form method="POST" action="contatos.php" onsubmit="return confirm('Remover este contato?');">
                        <input type="hidden" name="contato_id" value="<?= $registro['id'] ?>">
                        <button type="submit" name="acao" value="remover" class="btn btn-outline-danger btn-sm">Remover contato</button>
                    </form>

                <?php elseif ($relacao === 'pedido_enviado'): ?>
                    <div class="alert alert-warning">Pedido enviado. Aguardando resposta.</div>
                    <a href="<?= linkDetalhe('pedido', $pedidoId) ?>" class="btn btn-outline-secondary w-100">Ver pedido</a>

                <?php elseif ($relacao === 'pedido_recebido'): ?>
                    <div class="alert alert-info"><?= e($registro['nome']) ?> quer adicionar você aos contatos.</div>
                    <form method="POST" action="pedidos.php" class="d-flex gap-2">
                        <input type="hidden" name="pedido_id" value="<?= $pedidoId ?>">
                        <button name="acao" value="aceitar" class="btn btn-success flex-fill">Aceitar</button>
                        <button name="acao" value="recusar" class="btn btn-outline-danger flex-fill">Recusar</button>
                    </form>

                <?php else: ?>
                    <form method="POST">
                        <button type="submit" name="acao" value="solicitar" class="btn btn-primary w-100">Solicitar contato</button>
                    </form>
                    <small class="text-muted mt-2">A pessoa precisa aceitar o pedido para vocês conversarem.</small>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- ===== PEDIDO ===== -->
            <?php $cores = ['pendente' => 'warning', 'aceito' => 'success', 'recusado' => 'danger']; ?>
            <div class="card shadow p-4">
                <h1 class="h4 mb-3">Pedido de contato #<?= $registro['id'] ?>
                    <span class="badge bg-<?= $cores[$registro['status']] ?>"><?= ucfirst($registro['status']) ?></span>
                </h1>

                <div class="d-flex align-items-center justify-content-around text-center mb-3">
                    <?php foreach (['solicitante' => 'De', 'destinatario' => 'Para'] as $lado => $rotulo): $u = $registro[$lado]; ?>
                        <a href="<?= linkDetalhe('usuario', $u['id']) ?>" class="text-decoration-none text-reset">
                            <small class="text-muted d-block"><?= $rotulo ?></small>
                            <img src="uploads/<?= e($u['foto']) ?>" class="rounded-circle border my-1" width="70" height="70" style="object-fit: cover;">
                            <div class="fw-bold"><?= e($u['nome']) ?></div>
                            <small class="text-muted"><?= e($u['codigo_tag']) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>

                <p class="small text-muted mb-1">Enviado em: <?= date('d/m/Y H:i', strtotime($registro['criado_em'])) ?></p>
                <?php if ($registro['respondido_em']): ?>
                    <p class="small text-muted">Respondido em: <?= date('d/m/Y H:i', strtotime($registro['respondido_em'])) ?></p>
                <?php endif; ?>

                <?php if ($registro['status'] === 'pendente'): ?>
                    <form method="POST" action="pedidos.php" class="d-flex gap-2">
                        <input type="hidden" name="pedido_id" value="<?= $registro['id'] ?>">
                        <?php if ($registro['destinatario_id'] === $meuId): ?>
                            <button name="acao" value="aceitar" class="btn btn-success flex-fill">Aceitar</button>
                            <button name="acao" value="recusar" class="btn btn-outline-danger flex-fill">Recusar</button>
                        <?php else: ?>
                            <button name="acao" value="cancelar" class="btn btn-outline-secondary flex-fill">Cancelar pedido</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
                <a href="pedidos.php" class="btn btn-link mt-2">← Voltar para pedidos</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
