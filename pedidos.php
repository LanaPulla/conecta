<?php
require_once 'config.php';
exigirLogin();

$tituloPagina = "Pedidos de Contato - Conecta";
$meuId = $_SESSION['usuario_id'];

//  Aceitar, recusar ou cancelar um pedido 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'];
    $pedidoId = (int) $_POST['pedido_id'];
    $pedido = supabaseUm("pedidos_contato?id=eq.$pedidoId&status=eq.pendente&select=id,solicitante_id,destinatario_id");

    // Só quem recebeu pode aceitar/recusar; só quem enviou pode cancelar
    $souDestinatario = $pedido && $pedido['destinatario_id'] === $meuId;
    $souSolicitante = $pedido && $pedido['solicitante_id'] === $meuId;

    if ($acao === 'aceitar' && $souDestinatario) {
        supabaseRequest("pedidos_contato?id=eq.$pedidoId", 'PATCH', ['status' => 'aceito', 'respondido_em' => date('c')]);

        //vale para os dois usuários se um aceita o outro recebe
        supabaseRequest('contatos', 'POST', ['usuario_id' => $meuId, 'contato_id' => $pedido['solicitante_id']]);
        supabaseRequest('contatos', 'POST', ['usuario_id' => $pedido['solicitante_id'], 'contato_id' => $meuId]);
        registrarLog("Pedido de Contato Aceito");
        flash('success', 'Pedido aceito! O contato já está na sua lista.');
    } elseif ($acao === 'recusar' && $souDestinatario) {
        supabaseRequest("pedidos_contato?id=eq.$pedidoId", 'PATCH', ['status' => 'recusado', 'respondido_em' => date('c')]);
        registrarLog("Pedido de Contato Recusado");
        flash('secondary', 'Pedido recusado.');
    } elseif ($acao === 'cancelar' && $souSolicitante) {
        supabaseRequest("pedidos_contato?id=eq.$pedidoId", 'DELETE');
        registrarLog("Pedido de Contato Cancelado");
        flash('secondary', 'Pedido cancelado.');
    } else {
        flash('danger', 'Pedido não encontrado ou ação não permitida.');
    }

    redirecionar('pedidos.php');
}

//  Listas 
$recebidos = supabaseLista("pedidos_contato?destinatario_id=eq.$meuId&status=eq.pendente&order=criado_em.desc&select=id,criado_em,usuario:usuarios!pedidos_contato_solicitante_id_fkey(id,nome,codigo_tag,foto)");
$enviados = supabaseLista("pedidos_contato?solicitante_id=eq.$meuId&status=eq.pendente&order=criado_em.desc&select=id,criado_em,usuario:usuarios!pedidos_contato_destinatario_id_fkey(id,nome,codigo_tag,foto)");
$historico = supabaseLista("pedidos_contato?or=(solicitante_id.eq.$meuId,destinatario_id.eq.$meuId)&status=neq.pendente&order=respondido_em.desc&limit=10"
    . "&select=id,status,solicitante:usuarios!pedidos_contato_solicitante_id_fkey(nome),destinatario:usuarios!pedidos_contato_destinatario_id_fkey(nome)");

// lista de pedidos
function cartaoPedido($pedido, $botoes)
{
    $u = $pedido['usuario'];
    ?>
    <div class="list-group-item d-flex align-items-center justify-content-between flex-wrap gap-2">
        <a href="<?= linkDetalhe('usuario', $u['id']) ?>" class="d-flex align-items-center gap-3 text-decoration-none text-reset">
            <img src="uploads/<?= e($u['foto']) ?>" class="rounded-circle" width="50" height="50" style="object-fit: cover;">
            <div>
                <h6 class="mb-0"><?= e($u['nome']) ?></h6>
                <small class="text-muted"><?= e($u['codigo_tag']) ?> · <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></small>
            </div>
        </a>
        <form method="POST" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
            <a href="<?= linkDetalhe('pedido', $pedido['id']) ?>" class="btn btn-link btn-sm">Detalhes</a>
            <?= $botoes ?>
        </form>
    </div>
    <?php
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <?= pegarFlash() ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Pedidos de Contato</h1>
            <a href="buscar.php" class="btn btn-primary btn-sm">+ Buscar por #Tag</a>
        </div>

        <h2 class="h5 mt-4">Recebidos <span class="badge bg-danger"><?= count($recebidos) ?></span></h2>
        <?php if (!$recebidos): ?>
            <div class="alert alert-light border">Nenhum pedido aguardando sua resposta.</div>
        <?php endif; ?>
        <div class="list-group shadow-sm">
            <?php foreach ($recebidos as $p) {
                cartaoPedido($p, '<button name="acao" value="aceitar" class="btn btn-success btn-sm">Aceitar</button>
                                  <button name="acao" value="recusar" class="btn btn-outline-danger btn-sm">Recusar</button>');
            } ?>
        </div>

        <h2 class="h5 mt-4">Enviados <span class="badge bg-secondary"><?= count($enviados) ?></span></h2>
        <?php if (!$enviados): ?>
            <div class="alert alert-light border">Você não tem pedidos pendentes enviados.</div>
        <?php endif; ?>
        <div class="list-group shadow-sm">
            <?php foreach ($enviados as $p) {
                cartaoPedido($p, '<span class="badge bg-warning text-dark">Aguardando</span>
                                  <button name="acao" value="cancelar" class="btn btn-outline-secondary btn-sm">Cancelar</button>');
            } ?>
        </div>

        <?php if ($historico): ?>
            <h2 class="h5 mt-4">Respondidos recentemente</h2>
            <ul class="list-group small">
                <?php foreach ($historico as $h): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= e($h['solicitante']['nome']) ?> → <?= e($h['destinatario']['nome']) ?></span>
                        <a href="<?= linkDetalhe('pedido', $h['id']) ?>" class="badge text-decoration-none bg-<?= $h['status'] === 'aceito' ? 'success' : 'danger' ?>"><?= ucfirst($h['status']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
