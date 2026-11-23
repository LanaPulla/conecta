// Atualiza os números de avisos do menu (mensagens não lidas e pedidos)
// e o número de não lidas de cada contato na página "Meus Contatos".
// Pergunta ao servidor a cada 2 segundos.

function mostrarNumero(elemento, numero) {
    if (!elemento) return;
    elemento.textContent = numero;
    elemento.classList.toggle('d-none', numero === 0); // esconde quando é 0
}

function atualizarAvisos() {
    fetch('api_chat.php?acao=avisos')
        .then((resposta) => resposta.json())
        .then((dados) => {
            mostrarNumero(document.getElementById('badgeChat'), dados.nao_lidas);
            mostrarNumero(document.getElementById('badgePedidos'), dados.pedidos);

            document.querySelectorAll('[data-nao-lidas-contato]').forEach((el) => {
                mostrarNumero(el, dados.por_contato[el.dataset.naoLidasContato] || 0);
            });
        });
}

setInterval(atualizarAvisos, 2000);
