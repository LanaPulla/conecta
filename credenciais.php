<?php

// Supabase 
define('SUPABASE_URL', 'https://kkgqittziwoabkfljesq.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_dQ0LH4xyTu5vX9kE214Wig_OWaAQyPu');

// Gmail SMTP 
define('SMTP_EMAIL', 'lanapullaa@gmail.com');
define('SMTP_SENHA_APP', 'dgozytttsvvgiill');

// Google Gemini 
define('GEMINI_API_KEY', 'AQ.Ab8RN6KsZ-1CUQVF1MJ4BSCNTKHxd9ahbeA5_tLIcfzRp6GLJQ');
define('GEMINI_MODELO', 'gemini-flash-lite-latest');

// Dono do sistema: recebe as mensagens do Fale Conosco
define('EMAIL_DONO', 'lanapullaa@gmail.com');

// URL pública do sistema
$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
define('URL_BASE', ($__https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(str_replace(chr(92), '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/');
