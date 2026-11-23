<?php
// CONFIGURAÇÃO GERAL: sessão, log, e-mail, upload e funções auxiliares.
// Toda página começa com: require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;

date_default_timezone_set('America/Sao_Paulo');
session_start();

require_once __DIR__ . '/credenciais.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// Grava uma linha no sistema.log
function registrarLog($acao, $detalhes = '')
{
    $data = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
    $usuario = $_SESSION['usuario_email'] ?? 'Visitante';
    $linha = "[$data] [IP: $ip] [Usuário: $usuario] Ação: $acao" . ($detalhes ? " | $detalhes" : '');
    $linha = str_replace(["\r", "\n"], ' ', $linha); // uma ação = uma linha
    file_put_contents(__DIR__ . '/sistema.log', $linha . PHP_EOL, FILE_APPEND);
}

// Envia um e-mail
function enviarEmailSMTP($destinatario, $assunto, $mensagemHtml, $responderPara = null)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_EMAIL;
        $mail->Password = SMTP_SENHA_APP;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // No XAMPP muda para usar SSL false 
        if (ambienteLocal()) {
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
        }

        $mail->setFrom(SMTP_EMAIL, 'Conecta');
        $mail->addAddress($destinatario);
        if ($responderPara) {
            $mail->addReplyTo($responderPara);
        }
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagemHtml;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Escapa texto para exibir no HTML
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Manda para o login quem não estiver logado
function exigirLogin()
{
    if (!isset($_SESSION['usuario_id'])) {
        redirecionar('index.php');
    }
}

function redirecionar($url)
{
    header('Location: ' . $url);
    exit;
}

// Mensagem de sucesso ou falha após uma ação
function flash($tipo, $texto)
{
    $_SESSION['flash'] = '<div class="alert alert-' . $tipo . '">' . e($texto) . '</div>';
}

//mostra a mensagem do flash
function pegarFlash()
{
    $html = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $html;
}

function linkDetalhe($tipo, $id)
{
    return "detalhe.php?tipo=$tipo&id=$id";
}

function linkChat($contatoId = '')
{
    return 'chat-react/dist/index.html#/chat/' . $contatoId;
}

// Mensagens não lidas agrupadas para mostrar ao usuário 
function naoLidasPorContato($meuId)
{
    $linhas = supabaseLista("mensagens?destinatario_id=eq.$meuId&lida=eq.false&select=remetente_id");
    return array_count_values(array_column($linhas, 'remetente_id'));
}

// Relação entre usuários: 'eu', 'contato', 'pedido_enviado', 'pedido_recebido' ou 'nenhuma' 
//em busca.php usado para mostrar o status do usuário
//em detalhe.php usado para verificar se há relação em if 
function relacaoCom($meuId, $outroId, &$pedido = null)
{
    $pedido = null;
    if ($meuId === $outroId) {
        return 'eu';
    }
    if (supabaseUm("contatos?usuario_id=eq.$meuId&contato_id=eq.$outroId&select=contato_id")) {
        return 'contato';
    }
    $pedido = supabaseUm("pedidos_contato?status=eq.pendente&or=(and(solicitante_id.eq.$meuId,destinatario_id.eq.$outroId),and(solicitante_id.eq.$outroId,destinatario_id.eq.$meuId))&select=id,solicitante_id");
    if ($pedido) {
        return $pedido['solicitante_id'] === $meuId ? 'pedido_enviado' : 'pedido_recebido';
    }
    return 'nenhuma';
}

// Salva a foto e retorna o nome do arquivo ou uma mensagem de erro 
function salvarFoto($arquivo, &$erro)
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $arquivo['size'] > 2 * 1024 * 1024) {
        $erro = 'Envie uma foto de até 2 MB.';
        return null;
    }

    // Confere se é mesmo uma imagem 
    $extensoes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'];
    $info = getimagesize($arquivo['tmp_name']);
    if (!$info || !isset($extensoes[$info[2]])) {
        $erro = 'Formato inválido. Use JPG, PNG ou GIF.';
        return null;
    }

    $nome = uniqid('user_') . '.' . $extensoes[$info[2]];
    move_uploaded_file($arquivo['tmp_name'], __DIR__ . '/uploads/' . $nome);
    return $nome;
}
