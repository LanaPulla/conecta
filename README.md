# Conecta — Mensagens em Tempo Real

Trabalho da disciplina de **Programação Web II**.

O Conecta é uma plataforma de mensagens. Cada usuário recebe um código único (`#1234`). Com esse código, outras pessoas enviam um **pedido de contato**, e depois de aceito os dois conversam em **tempo real**. No chat, é possível perguntar à IA com `@gemini <pergunta>`.

| | |
|---|---|
| **Sistema online** | `PREENCHER: https://...` |
| **Repositório Git** | `PREENCHER: https://github.com/...` |
| **Banco (SQL)** | [`banco/conecta.sql`](banco/conecta.sql) |

## Usuários de teste

| E-mail | Senha | Observação |
|---|---|---|
| `PREENCHER` | `PREENCHER` | Usuário de teste 1 |
| `PREENCHER` | `PREENCHER` | Usuário de teste 2 |

## Tecnologias

- **PHP 8 puro** (sem frameworks) + **PHPMailer** (liberado pelo professor)
- **PostgreSQL** no Supabase, acessado pela API REST com cURL (liberado pelo professor)
- **React 19 + React Router 7** (chat SPA) + **Supabase Realtime**
- **Bootstrap 5**
- **Google Gemini** (LLM)

## Mapa de requisitos

| Requisito do enunciado | Onde está |
|---|---|
| Login, recuperação de senha e foto do usuário salva em arquivo | `index.php`, `cadastro.php`, `editar_perfil.php`, `uploads/` |
| Cadastro com validação (front e back) | `cadastro.php` + `js/validacao.js` |
| Termos de serviço e política de plágio (LGPD) | `termos.php` |
| Fale Conosco com validação e envio de e-mail via PHP | `faq_contato.php` (PHPMailer) |
| Meta plagiarism + Open Graph + JSON-LD nas telas públicas | `header.php` (+ `FAQPage` no FAQ) |
| Get-one dinâmico (1 PHP para todos os recursos) | `detalhe.php?tipo=usuario\|pedido&id=` |
| 2 cadastros simples | `usuarios` (cadastro/editar perfil) e `assuntos_contato` (opções de assunto do Fale Conosco) |
| 1 cadastro 1..n | `assuntos_contato` → `contatos_site` (cada mensagem do Fale Conosco tem um assunto; "Outro" abre campo de texto) |
| 2 cadastros n..n | `contatos` e `pedidos_contato` (usuário ↔ usuário) |
| Telas públicas | Login, Cadastro, FAQ/Fale Conosco e Termos |
| BD com 6 tabelas | `usuarios`, `contatos`, `pedidos_contato`, `mensagens`, `assuntos_contato`, `contatos_site` |
| Recurso com LLM | `@gemini <pergunta>` no chat (`ia.php`, `api_chat.php`) |
| Logs em arquivo de todas as ações | `registrarLog()` em `config.php` → arquivo `sistema.log` (bloqueado para acesso via URL) |
| Arquivo PHP só para a conexão com o banco | `conexao.php` |
| Header e footer incluídos | `header.php` e `footer.php` |
| AJAX / SPA + React Router | `chat-react/` (rotas `#/chat` e `#/chat/:contatoId`) consumindo `api_chat.php` |

## Estrutura

```
├── index.php            login + recuperação de senha
├── cadastro.php         cadastro de usuário (com foto)
├── faq_contato.php      FAQ + Fale Conosco
├── termos.php           termos de uso e privacidade
├── buscar.php           busca por #Tag
├── detalhe.php          get-one dinâmico
├── editar_perfil.php    nome, legenda, foto, senha
├── contatos.php         lista e remove contatos
├── pedidos.php          aceitar, recusar e cancelar pedidos
├── api_chat.php         API JSON do chat
├── ia.php               integração com o Gemini
├── config.php           sessão, logs, e-mail, upload, helpers
├── conexao.php          conexão com o banco (Supabase REST)
├── header.php / footer.php
├── js/validacao.js     validação extra do front (o resto é HTML5)
├── js/avisos.js        atualiza os números de avisos do menu
├── chat-react/          código do chat (build em chat-react/dist)
└── banco/               scripts SQL
```

## Como rodar localmente (XAMPP)

1. Copie a pasta para `C:\xampp\htdocs\projetoWebII`.
2. Copie `credenciais.exemplo.php` para `credenciais.php` e preencha as chaves (Supabase, Gmail, Gemini).
3. No Supabase, rode os scripts de `banco/` (ou importe `banco/conecta.sql`).
4. Acesse `http://localhost/projetoWebII/`.
5. (Opcional) Para alterar o chat: `cd chat-react && npm install && npm run build`.

## Documentação do projeto

- [PRD.md](PRD.md): requisitos e plano de ação
- [TECHSPEC.md](TECHSPEC.md): especificação técnica
- [TASKS.md](TASKS.md): tarefas
