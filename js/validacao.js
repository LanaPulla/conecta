
// Confirmar senha
document.querySelectorAll('[data-confirma]').forEach((campo) => {
    const senha = document.getElementById(campo.dataset.confirma);
    const conferir = () => campo.setCustomValidity(campo.value === senha.value ? '' : 'As senhas não conferem.');
    campo.addEventListener('input', conferir);
    senha.addEventListener('input', conferir);
});

// Tamanho máximo da foto
document.querySelectorAll('[data-max-mb]').forEach((campo) => {
    campo.addEventListener('change', () => {
        const limite = campo.dataset.maxMb * 1024 * 1024;
        const grande = campo.files.length && campo.files[0].size > limite;
        campo.setCustomValidity(grande ? `A foto deve ter no máximo ${campo.dataset.maxMb} MB.` : '');
    });
});

// Contador de caracteres
document.querySelectorAll('[data-contador]').forEach((campo) => {
    const contador = document.createElement('div');
    contador.className = 'form-text text-end';
    campo.after(contador);
    const atualizar = () => (contador.textContent = `${campo.value.length} / ${campo.maxLength}`);
    campo.addEventListener('input', atualizar);
    atualizar();
});

// Fale Conosco: assunto "Outro" 
const selectAssunto = document.getElementById('assunto_id');
if (selectAssunto) {
    const caixaOutro = document.getElementById('campoOutro');
    const campoOutro = document.getElementById('assunto_outro');
    const mostrarOutro = () => {
        const ehOutro = selectAssunto.selectedOptions[0]?.hasAttribute('data-outro') || false;
        caixaOutro.classList.toggle('d-none', !ehOutro);
        campoOutro.required = ehOutro;
    };
    selectAssunto.addEventListener('change', mostrarOutro);
    mostrarOutro();
}
