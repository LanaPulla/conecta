<?php
// Cada página pode definir $tituloPagina e $descricaoPagina antes de incluir este arquivo.
$tituloPagina = $tituloPagina ?? 'Conecta - Mensagens em Tempo Real';
$descricaoPagina = $descricaoPagina ?? 'Conecta: adicione amigos pelo código #Tag e converse em tempo real.';
$logado = isset($_SESSION['usuario_id']);

// Números dos avisos no menu (pedidos pendentes e mensagens não lidas)
$qtdPedidos = 0;
$qtdNaoLidas = 0;
if ($logado) {
    $qtdPedidos = count(supabaseLista('pedidos_contato?destinatario_id=eq.' . $_SESSION['usuario_id'] . '&status=eq.pendente&select=id'));
    $qtdNaoLidas = array_sum(naoLidasPorContato($_SESSION['usuario_id']));
}

// Dados estruturados (JSON-LD) para buscadores
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'Conecta',
    'url' => URL_BASE,
    'description' => $descricaoPagina,
];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?></title>
    <meta name="description" content="<?= e($descricaoPagina) ?>">

    <!-- Plágio  -->
    <meta name="plagiarism" content="original-academic-work-web2-2026">

    <!-- Open Graph  -->
    <meta property="og:title" content="<?= e($tituloPagina) ?>">
    <meta property="og:description" content="<?= e($descricaoPagina) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e(URL_BASE . basename($_SERVER['SCRIPT_NAME'])) ?>">
    <meta property="og:image" content="<?= e(URL_BASE) ?>img/og-conecta.png">

    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Conecta</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="faq_contato.php">FAQ & Fale Conosco</a>
                    <?php if ($logado): ?>
                        <a class="nav-link" href="<?= linkDetalhe('usuario', $_SESSION['usuario_id']) ?>">Meu Perfil</a>
                        <a class="nav-link" href="buscar.php">Buscar #Tag</a>
                        <a class="nav-link" href="contatos.php">Meus Contatos</a>
                        <!-- js/avisos.js atualiza estes números sozinho -->
                        <a class="nav-link" href="pedidos.php">Pedidos <span id="badgePedidos" class="badge bg-danger <?= $qtdPedidos ? '' : 'd-none' ?>"><?= $qtdPedidos ?></span></a>
                        <a class="nav-link text-warning fw-bold" href="<?= linkChat() ?>">Abrir Chat (<?= e($_SESSION['usuario_tag']) ?>) <span id="badgeChat" class="badge bg-danger <?= $qtdNaoLidas ? '' : 'd-none' ?>"><?= $qtdNaoLidas ?></span></a>
                        <a class="nav-link text-danger" href="logout.php">Sair</a>
                    <?php else: ?>
                        <a class="nav-link" href="index.php">Entrar</a>
                        <a class="nav-link" href="cadastro.php">Cadastrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <div class="container flex-grow-1">
