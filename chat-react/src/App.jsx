import { useEffect, useRef, useState } from 'react';
import { HashRouter, Routes, Route, Navigate, useNavigate, useParams } from 'react-router-dom';
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  'https://kkgqittziwoabkfljesq.supabase.co',
  'sb_publishable_dQ0LH4xyTu5vX9kE214Wig_OWaAQyPu'
);

// subir pastas acima, PHP duas pastas acima
const BASE = '../../';

// Chama a API PHP. Com "corpo" faz POST; sem faz GET.
async function api(acao, parametros = '', corpo = null) {
  const resposta = await fetch(`${BASE}api_chat.php?acao=${acao}${parametros}`, {
    method: corpo ? 'POST' : 'GET',
    body: corpo ? JSON.stringify(corpo) : undefined,
  });
  if (resposta.status === 401) {
    window.location.href = `${BASE}index.php`; // se a sessão expirou volta para o login
  }
  const dados = await resposta.json();
  if (!resposta.ok) throw new Error(dados.mensagem);
  return dados;
}

function Avatar({ pessoa }) {
  if (!pessoa) return <span className="avatar avatar-md avatar-fallback">CO</span>;
  return <img className="avatar avatar-md" src={`${BASE}uploads/${pessoa.foto}`} alt={pessoa.nome} />;
}

function ListaContatos({ contatos, contatoId }) {
  const navigate = useNavigate();
  const [filtro, setFiltro] = useState('');

  const busca = filtro.toLowerCase();
  const visiveis = contatos.filter(
    (c) => c.nome.toLowerCase().includes(busca) || c.codigo_tag.includes(busca)
  );

  return (
    <aside className="contacts-panel">
      <div className="contacts-heading">
        <h1 className="h4 mb-1">Mensagens</h1>
        <a className="btn btn-outline-primary btn-sm" href={`${BASE}buscar.php`}>+ Adicionar</a>
      </div>
      <input
        className="form-control mb-3"
        placeholder="Pesquisar nome ou #tag"
        value={filtro}
        onChange={(e) => setFiltro(e.target.value)}
      />
      <div className="contact-list">
        {visiveis.map((contato) => (
          <button
            type="button"
            key={contato.id}
            className={`contact-item ${contato.id === contatoId ? 'active' : ''} ${contato.nao_lidas > 0 ? 'nao-lida' : ''}`}
            onClick={() => navigate(`/chat/${contato.id}`)}
          >
            <Avatar pessoa={contato} />
            <span className="contact-info">
              <strong>{contato.nome}</strong>
              <small>{contato.nao_lidas > 0 ? 'Nova mensagem' : contato.codigo_tag}</small>
            </span>
            {contato.nao_lidas > 0 && <span className="badge rounded-pill bg-danger ms-auto">{contato.nao_lidas}</span>}
          </button>
        ))}
        {contatos.length === 0 && (
          <div className="empty-contacts">
            Você ainda não tem contatos. <a href={`${BASE}buscar.php`}>Busque por #Tag</a>.
          </div>
        )}
      </div>
    </aside>
  );
}

function Mensagem({ mensagem, minha }) {
  const hora = new Date(mensagem.data_envio).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

  if (mensagem.gerada_por_ia) {
    return (
      <div className="message-row ia">
        <div className="message-bubble message-ia">
          <span className="ia-label">🤖 Gemini</span>
          <span className="ia-texto">{mensagem.conteudo}</span>
          <small>{hora}</small>
        </div>
      </div>
    );
  }

  return (
    <div className={`message-row ${minha ? 'mine' : 'theirs'}`}>
      <div className="message-bubble">
        <span>{mensagem.conteudo}</span>
        <small>{hora}</small>
      </div>
    </div>
  );
}

function SalaChat() {
  const { contatoId } = useParams();
  const [eu, setEu] = useState(null);
  const [contatos, setContatos] = useState([]);
  const [mensagens, setMensagens] = useState([]);
  const [texto, setTexto] = useState('');
  const [esperandoIa, setEsperandoIa] = useState(false);
  const [erro, setErro] = useState('');
  const fimDaLista = useRef(null);

  // O listener do Realtime é criado uma vez só; o ref deixa ele saber qual conversa está aberta agora
  const conversaAberta = useRef(contatoId);

  const contatoAtual = contatos.find((c) => c.id === contatoId);

  // Adiciona mensagens no fim da conversa sem repetir
  // (a mesma mensagem pode chegar pela API e pelo Realtime)
  const adicionarMensagens = (...novas) =>
    setMensagens((atuais) => {
      const ids = atuais.map((m) => m.id);
      return [...atuais, ...novas.filter((m) => m && !ids.includes(m.id))];
    });

  // Recarrega a lista de contatos com os números de não lidas
  const carregarContatos = () =>
    api('contatos').then((lista) =>
      setContatos(lista.map((c) => (c.id === conversaAberta.current ? { ...c, nao_lidas: 0 } : c)))
    );

  // atualiza os contatos a cada 2seg
  useEffect(() => {
    api('eu').then(setEu);
    carregarContatos();
    const timer = setInterval(carregarContatos, 2000);
    return () => clearInterval(timer);
  }, []);

  // carrega as mensagens da conversa
  useEffect(() => {
    conversaAberta.current = contatoId;
    setMensagens([]);
    setErro('');
    if (!contatoId) return;
    let ignorar = false; // se o usuário trocar de contato antes da resposta chegar
    api('historico', `&contato_id=${contatoId}`)
      .then((lista) => {
        if (ignorar) return;
        setMensagens(lista);
        // A API já marcou como lidas
        setContatos((lista) => lista.map((c) => (c.id === contatoId ? { ...c, nao_lidas: 0 } : c)));
      })
      .catch((e) => setErro(e.message));
    return () => (ignorar = true);
  }, [contatoId]);

  // Tempo real: ouve toda mensagem nova gravada no banco
  useEffect(() => {
    if (!eu) return;

    const canal = supabase
      .channel(`mensagens-${eu.id}`)
      .on('postgres_changes', { event: 'INSERT', schema: 'public', table: 'mensagens' }, ({ new: nova }) => {
        const recebida = nova.destinatario_id === eu.id;
        const enviada = nova.remetente_id === eu.id;
        if (!recebida && !enviada) return; // conversa de outras pessoas

        const outro = recebida ? nova.remetente_id : nova.destinatario_id;
        if (outro === conversaAberta.current) {
          // Conversa aberta mostra na tela e marca como lida
          adicionarMensagens(nova);
          if (recebida) api('marcar_lidas', '', { contato_id: outro });
        } else if (recebida) {
          // Outra conversa soma 1 no número de não lidas daquele contato
          setContatos((lista) => lista.map((c) => (c.id === outro ? { ...c, nao_lidas: c.nao_lidas + 1 } : c)));
        }
      })
      .subscribe();

    return () => supabase.removeChannel(canal);
  }, [eu]);

  // Total de não lidas no título da aba
  const totalNaoLidas = contatos.reduce((soma, c) => soma + c.nao_lidas, 0);
  useEffect(() => {
    document.title = `${totalNaoLidas ? `(${totalNaoLidas}) ` : ''}Chat - Conecta`;
  }, [totalNaoLidas]);

  // Rola até a última mensagem
  useEffect(() => {
    fimDaLista.current?.scrollIntoView({ behavior: 'smooth' });
  }, [mensagens, esperandoIa]);

  const enviarMensagem = async (e) => {
    e.preventDefault();
    const conteudo = texto.trim();
    if (!conteudo) return;

    setTexto('');
    setErro('');
    setEsperandoIa(conteudo.toLowerCase().startsWith('@gemini '));
    try {
      const r = await api('enviar', '', { contato_id: contatoId, conteudo });
      adicionarMensagens(r.mensagem, r.resposta_ia);
    } catch (err) {
      setErro(err.message);
      setTexto(conteudo); // devolve o texto para tentar de novo
    }
    setEsperandoIa(false);
  };

  return (
    <div className="app-shell">
      <header className="navbar navbar-dark bg-dark app-header">
        <div className="container">
          <a className="navbar-brand fw-bold" href={`${BASE}index.php`}>Conecta</a>
          <nav className="navbar-nav ms-auto flex-row flex-wrap gap-3">
            <a className="nav-link" href={`${BASE}faq_contato.php`}>FAQ &amp; Fale Conosco</a>
            {eu && <a className="nav-link" href={`${BASE}detalhe.php?tipo=usuario&id=${eu.id}`}>Meu Perfil</a>}
            <a className="nav-link" href={`${BASE}buscar.php`}>Buscar #Tag</a>
            <a className="nav-link" href={`${BASE}contatos.php`}>Meus Contatos</a>
            <a className="nav-link" href={`${BASE}pedidos.php`}>Pedidos</a>
            <a className="nav-link text-danger" href={`${BASE}logout.php`}>Sair</a>
          </nav>
        </div>
      </header>

      <main className="container flex-grow-1 py-3 py-lg-4">
        <div className="chat-layout">
          <ListaContatos contatos={contatos} contatoId={contatoId} />

          <section className="conversation-card">
            <div className="conversation-header">
              <Avatar pessoa={contatoAtual} />
              <div>
                <h2 className="h5 mb-0">{contatoAtual?.nome || 'Conversa'}</h2>
                <small className="text-muted">{contatoAtual?.codigo_tag || 'Mensagens em tempo real'}</small>
              </div>
            </div>

            <div className="messages-area">
              {!contatoId && (
                <div className="empty-conversation">
                  <span>💬</span>
                  <p>Escolha um contato ao lado para começar a conversar.</p>
                </div>
              )}

              {contatoId && mensagens.length === 0 && (
                <div className="empty-conversation">
                  <span>💬</span>
                  <p>Dica: escreva <code>@gemini</code> + sua pergunta para a IA responder aqui.</p>
                </div>
              )}

              {mensagens.map((m) => (
                <Mensagem key={m.id} mensagem={m} minha={m.remetente_id === eu?.id} />
              ))}

              {esperandoIa && (
                <div className="message-row ia">
                  <div className="message-bubble message-ia digitando">🤖 Gemini está digitando…</div>
                </div>
              )}
              <div ref={fimDaLista} />
            </div>

            {erro && <div className="alert alert-danger m-2 py-2 small">{erro}</div>}

            {contatoAtual && (
              <form className="message-form" onSubmit={enviarMensagem}>
                <input
                  className="form-control"
                  value={texto}
                  maxLength={2000}
                  onChange={(e) => setTexto(e.target.value)}
                  placeholder="Digite uma mensagem ou @gemini + pergunta"
                />
                <button type="submit" className="btn btn-success px-4" disabled={esperandoIa}>Enviar</button>
              </form>
            )}
          </section>
        </div>
      </main>

      <footer className="bg-dark text-white text-center py-3 mt-4">
        <small>
          Conecta &copy; 2026 - Trabalho acadêmico original. | <a href={`${BASE}termos.php`} className="text-info">Termos de Uso</a>
        </small>
      </footer>
    </div>
  );
}

// Rotas de chat individual e geral
export default function App() {
  return (
    <HashRouter>
      <Routes>
        <Route path="/chat" element={<SalaChat />} />
        <Route path="/chat/:contatoId" element={<SalaChat />} />
        <Route path="*" element={<Navigate to="/chat" />} />
      </Routes>
    </HashRouter>
  );
}
