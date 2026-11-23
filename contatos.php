<?php
require_once 'config.php';
exigirLogin();

$tituloPagina = "Meus Contatos - Conecta";
$meuId = $_SESSION['usuario_id'];

//  Remover contato 
if (($_POST['acao'] ?? '') === 'remover' && uuidValido($_POST['contato_id'] ?? '')) {
    $outroId = $_POST['contato_id'];
    supabaseRequest("contatos?usuario_id=eq.$meuId&contato_id=eq.$outroId", 'DELETE');
    supabaseRequest("contatos?usuario_id=eq.$outroId&contato_id=eq.$meuId", 'DELETE');
    registrarLog("Contato Removido");
    flash('secondary', 'Contato removido.');
    redirecionar('contatos.php');
}

//  Lista de contatos com os dados de cada amigo 
$contatos = supabaseLista("contatos?usuario_id=eq.$meuId&select=usuarios!contatos_contato_id_fkey(id,nome,codigo_tag,foto,bio)&order=data_adicao.desc");
$naoLidas = naoLidasPorContato($meuId);

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <?= pegarFlash() ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Meus Contatos <span class="badge bg-secondary fs-6"><?= count($contatos) ?></span></h1>
            <a href="buscar.php" class="btn btn-primary btn-sm">+ Adicionar por #Tag</a>
        </div>

        <?php if (!$contatos): ?>
            <div class="alert alert-info">Você ainda não tem contatos. Clique em <strong>+ Adicionar por #Tag</strong> e envie um pedido!</div>
        <?php endif; ?>

        <div class="list-group shadow-sm">
            <?php foreach ($contatos as $item):
                $amigo = $item['usuarios'];
                $qtd = $naoLidas[$amigo['id']] ?? 0;
            ?>
                <div class="list-group-item d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <a href="<?= linkDetalhe('usuario', $amigo['id']) ?>" class="d-flex align-items-center gap-3 text-decoration-none text-reset">
                        <img src="uploads/<?= e($amigo['foto']) ?>" class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                        <div>
                            <h6 class="mb-0"><?= e($amigo['nome']) ?> <small class="text-muted"><?= e($amigo['codigo_tag']) ?></small></h6>
                            <small class="text-muted"><?= e($amigo['bio']) ?></small>
                        </div>
                    </a>
                    <div class="d-flex gap-2">
                        <a href="<?= linkChat($amigo['id']) ?>" class="btn btn-success btn-sm">
                            Enviar mensagem
                            <!-- js/avisos.js atualiza este número -->
                            <span data-nao-lidas-contato="<?= $amigo['id'] ?>" class="badge bg-danger <?= $qtd ? '' : 'd-none' ?>"><?= $qtd ?></span>
                        </a>
                        <form method="POST" onsubmit="return confirm('Remover este contato?');">
                            <input type="hidden" name="contato_id" value="<?= $amigo['id'] ?>">
                            <button type="submit" name="acao" value="remover" class="btn btn-outline-danger btn-sm">Remover</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
