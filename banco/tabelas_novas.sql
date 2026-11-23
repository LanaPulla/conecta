-- Tabelas novas + coluna da IA
-- Rodar no Supabase: SQL Editor -> New query -> colar -> Run
-- (rodar DEPOIS de pedidos_contato.sql)

-- 1) Mensagens geradas pelo @gemini
ALTER TABLE mensagens ADD COLUMN IF NOT EXISTS gerada_por_ia boolean DEFAULT false;

-- 2) Assuntos do Fale Conosco (cadastro simples)
--    "outro = true" faz o front abrir um campo de texto para digitar o assunto
CREATE TABLE IF NOT EXISTS assuntos_contato (
  id serial PRIMARY KEY,
  nome varchar(60) NOT NULL,
  outro boolean NOT NULL DEFAULT false,
  ordem int NOT NULL DEFAULT 0
);

INSERT INTO assuntos_contato (nome, outro, ordem)
SELECT v.nome, v.outro, v.ordem
FROM (VALUES
  ('Dúvida sobre como usar o Conecta', false, 1),
  ('Problema com login ou senha',      false, 2),
  ('Erro no chat ou nas mensagens',    false, 3),
  ('Pedidos de contato',               false, 4),
  ('Privacidade e meus dados (LGPD)',  false, 5),
  ('Denúncia de abuso ou plágio',      false, 6),
  ('Sugestão',                         false, 7),
  ('Outro',                            true,  99)
) AS v(nome, outro, ordem)
WHERE NOT EXISTS (SELECT 1 FROM assuntos_contato);

-- 3) Mensagens do Fale Conosco: assuntos_contato 1..n contatos_site
CREATE TABLE IF NOT EXISTS contatos_site (
  id serial PRIMARY KEY,
  assunto_id int NOT NULL REFERENCES assuntos_contato(id),
  assunto_outro varchar(100),          -- preenchido só quando o assunto é "Outro"
  nome varchar(100) NOT NULL,
  email varchar(150) NOT NULL,
  mensagem text NOT NULL,
  criado_em timestamptz DEFAULT now()
);

-- 4) Mesmo padrão de acesso das outras tabelas (o PHP usa a chave publishable)
ALTER TABLE assuntos_contato ENABLE ROW LEVEL SECURITY;
ALTER TABLE contatos_site    ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "acesso_api_assuntos" ON assuntos_contato;
CREATE POLICY "acesso_api_assuntos" ON assuntos_contato
  FOR ALL TO anon USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "acesso_api_contato" ON contatos_site;
CREATE POLICY "acesso_api_contato" ON contatos_site
  FOR ALL TO anon USING (true) WITH CHECK (true);

GRANT SELECT, INSERT, UPDATE, DELETE ON assuntos_contato, contatos_site TO anon;
GRANT USAGE, SELECT ON SEQUENCE assuntos_contato_id_seq, contatos_site_id_seq TO anon;

-- 5) Realtime: garante que a tabela mensagens está publicada
DO $$
BEGIN
  ALTER PUBLICATION supabase_realtime ADD TABLE mensagens;
EXCEPTION WHEN duplicate_object THEN
  NULL; -- já estava publicada
END $$;
