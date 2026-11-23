<?php
// CONEXÃO COM O BANCO: Supabase (PostgreSQL) pela API REST.
// Único arquivo que conversa com o banco de dados.

require_once __DIR__ . '/credenciais.php';

// Faz uma requisição à API. Retorna ['status' => código HTTP, 'dados' => resposta em array]
function supabaseRequest($endpoint, $metodo = 'GET', $dados = null)
{
    $ch = curl_init(SUPABASE_URL . '/rest/v1/' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_SSL_VERIFYPEER => !ambienteLocal(),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation', // devolve a linha criada/alterada
        ],
    ]);
    if ($dados !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    }

    $resposta = json_decode((string) curl_exec($ch), true);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['status' => $status, 'dados' => $resposta];
}

// Todas as linhas de uma consulta ([] se der erro)
function supabaseLista($endpoint)
{
    $r = supabaseRequest($endpoint);
    return ($r['status'] === 200 && is_array($r['dados'])) ? $r['dados'] : [];
}

// Só a primeira linha (ou null)
function supabaseUm($endpoint)
{
    return supabaseLista($endpoint)[0] ?? null;
}

// Confere se o id tem formato de uuid. Impede que alguém coloque filtros extras na URL da API.
function uuidValido($id)
{
    return is_string($id) && preg_match('/^[0-9a-f-]{36}$/i', $id);
}

// true quando roda no XAMPP (localhost), onde o SSL não é verificado
function ambienteLocal()
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1');
}
