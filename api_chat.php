<?php
// API do chat  React (chat-react) e o js/avisos.js chamam este arquivo.

require_once 'config.php';
require_once 'ia.php';

header('Content-Type: application/json; charset=utf-8');

function responder($dados, $status = 200)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// Só aceita conversar com quem está na minha lista de contatos
function ehMeuContato($eu, $contatoId)
{
    return uuidValido($contatoId) && supabaseUm("contatos?usuario_id=eq.$eu&contato_id=eq.$contatoId&select=contato_id");
}

function salvarMensagem($de, $para, $conteudo, $geradaPorIa = false)
{
    $r = supabaseRequest('mensagens', 'POST', [
        'remetente_id' => $de,
        'destinatario_id' => $para,
        'conteudo' => $conteudo,
        'gerada_por_ia' => $geradaPorIa,
    ]);
    return $r['dados'][0] ?? null; // a mensagem criada
}

function marcarComoLidas($eu, $contatoId)
{
    supabaseRequest("mensagens?remetente_id=eq.$contatoId&destinatario_id=eq.$eu&lida=eq.false", 'PATCH', ['lida' => true]);
}

if (!isset($_SESSION['usuario_id'])) {
    responder(['mensagem' => 'Faça login para usar o chat.'], 401);
}

$eu = $_SESSION['usuario_id'];
$acao = $_GET['acao'] ?? '';
$entrada = json_decode(file_get_contents('php://input'), true) ?? []; 
$contatoId = $_GET['contato_id'] ?? $entrada['contato_id'] ?? '';

// Dados do usuário logado
if ($acao === 'eu') {
    responder(supabaseUm("usuarios?id=eq.$eu&select=id,nome,codigo_tag,foto"));
}

// Meus contatos, cada um com a quantidade de mensagens não lidas
if ($acao === 'contatos') {
    $linhas = supabaseLista("contatos?usuario_id=eq.$eu&select=usuarios!contatos_contato_id_fkey(id,nome,codigo_tag,foto)");
    $naoLidas = naoLidasPorContato($eu);
    $contatos = [];
    foreach ($linhas as $linha) {
        $contato = $linha['usuarios'];
        $contato['nao_lidas'] = $naoLidas[$contato['id']] ?? 0;
        $contatos[] = $contato;
    }
    responder($contatos);
}

// Números dos avisos do menu (usado pelo js/avisos.js)
if ($acao === 'avisos') {
    $porContato = naoLidasPorContato($eu);
    responder([
        'nao_lidas' => array_sum($porContato),
        'por_contato' => (object) $porContato,
        'pedidos' => count(supabaseLista("pedidos_contato?destinatario_id=eq.$eu&status=eq.pendente&select=id")),
    ]);
}

// Daqui para baixo, todas as ações são sobre uma conversa com um contato
if (!ehMeuContato($eu, $contatoId)) {
    responder(['mensagem' => 'Esta pessoa não está nos seus contatos.'], 403);
}

// Mensagens da conversa (abrir a conversa = ler as mensagens)
if ($acao === 'historico') {
    $mensagens = supabaseLista("mensagens?or=(and(remetente_id.eq.$eu,destinatario_id.eq.$contatoId),and(remetente_id.eq.$contatoId,destinatario_id.eq.$eu))"
        . "&select=id,remetente_id,destinatario_id,conteudo,data_envio,gerada_por_ia&order=data_envio.asc");
    marcarComoLidas($eu, $contatoId);
    responder($mensagens);
}

// Marca como lida uma mensagem que chegou com a conversa aberta
if ($acao === 'marcar_lidas') {
    marcarComoLidas($eu, $contatoId);
    responder(['ok' => true]);
}

// Envia uma mensagem. Se começar com "@gemini", a IA responde na mesma conversa.
if ($acao === 'enviar') {
    $conteudo = trim($entrada['conteudo'] ?? '');
    if ($conteudo === '' || mb_strlen($conteudo) > 2000) {
        responder(['mensagem' => 'A mensagem deve ter entre 1 e  caracteres.'], 400);
    }

    $mensagem = salvarMensagem($eu, $contatoId, $conteudo);
    if (!$mensagem) {
        responder(['mensagem' => 'Não foi possível enviar a mensagem.'], 500);
    }
    registrarLog("Mensagem Enviada");

    $respostaIa = null;
    if (preg_match('/^@gemini\s+(.+)$/isu', $conteudo, $partes)) {
        $respostaIa = salvarMensagem($eu, $contatoId, perguntarGemini($partes[1]), true);
        registrarLog("Pergunta ao Gemini");
    }
    responder(['mensagem' => $mensagem, 'resposta_ia' => $respostaIa]);
}

responder(['mensagem' => 'Ocorreu um problema'], 500);
