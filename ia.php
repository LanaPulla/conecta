<?php
// INTEGRAÇÃO COM A IA (Google Gemini)

function perguntarGemini($pergunta)
{
    $instrucao = 'Você é o assistente do chat Conecta. Responda em português do Brasil, '
        . 'de forma curta (até 5 frases), clara e educada. Não use formatação Markdown.';

    $dados = [
        'systemInstruction' => ['parts' => [['text' => $instrucao]]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $pergunta]]]],
        'generationConfig' => ['maxOutputTokens' => 400],
    ];

    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODELO . ':generateContent');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => !ambienteLocal(),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . GEMINI_API_KEY],
        CURLOPT_POSTFIELDS => json_encode($dados),
    ]);
    $json = json_decode((string) curl_exec($ch), true);

    $resposta = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
    return $resposta ?: '⚠️ Não consegui responder agora. Tente de novo em instantes.';
}
