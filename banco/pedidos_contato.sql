-- Pedidos de contato (usuarios n..n usuarios)
-- Rodar no Supabase: SQL Editor -> New query -> colar -> Run

CREATE TABLE IF NOT EXISTS pedidos_contato (
  id serial PRIMARY KEY,
  solicitante_id uuid NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  destinatario_id uuid NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  status varchar(10) NOT NULL DEFAULT 'pendente'
    CHECK (status IN ('pendente', 'aceito', 'recusado')),
  criado_em timestamptz DEFAULT now(),
  respondido_em timestamptz,
  CHECK (solicitante_id <> destinatario_id)
);

-- Mesmo padrão de acesso das outras tabelas (o PHP usa a chave publishable)
ALTER TABLE pedidos_contato ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "acesso_api_pedidos" ON pedidos_contato;
CREATE POLICY "acesso_api_pedidos" ON pedidos_contato
  FOR ALL TO anon USING (true) WITH CHECK (true);

GRANT SELECT, INSERT, UPDATE, DELETE ON pedidos_contato TO anon;
GRANT USAGE, SELECT ON SEQUENCE pedidos_contato_id_seq TO anon;
