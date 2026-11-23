<?php
// Copie este arquivo para "credenciais.php" e preencha com os seus dados.

// Supabase (PostgreSQL)
define('SUPABASE_URL', 'https://SEU-PROJETO.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_XXXXXXXX');

// Gmail SMTP (senha de app: https://myaccount.google.com/apppasswords)
define('SMTP_EMAIL', 'seu.email@gmail.com');
define('SMTP_SENHA_APP', 'xxxxxxxxxxxxxxxx');

// Google Gemini (https://aistudio.google.com/apikey)
define('GEMINI_API_KEY', 'SUA_CHAVE_GEMINI_AQUI');
define('GEMINI_MODELO', 'gemini-flash-lite-latest');

// Dono do sistema: recebe as mensagens do Fale Conosco
define('EMAIL_DONO', 'seu.email@gmail.com');

// URL pública do sistema: detectada automaticamente (funciona no XAMPP e na Locaweb)
$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
define('URL_BASE', ($__https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(str_replace(chr(92), '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/');
