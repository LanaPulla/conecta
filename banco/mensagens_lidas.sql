-- Controle de mensagens não lidas (aviso de nova mensagem)
-- Rodar no Supabase: SQL Editor -> New query -> colar -> Run

ALTER TABLE mensagens ADD COLUMN IF NOT EXISTS lida boolean NOT NULL DEFAULT false;

-- As mensagens que já existiam contam como lidas (evita um aviso enorme no 1º acesso)
UPDATE mensagens SET lida = true WHERE lida = false;
