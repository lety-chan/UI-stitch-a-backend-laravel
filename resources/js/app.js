const assistantForm = document.querySelector('#assistant-form');
const assistantMessage = document.querySelector('#assistant-message');
const voiceButton = document.querySelector('#voice-input');
const voiceStatus = document.querySelector('#voice-status');

document.querySelectorAll('[data-chat-prompt]').forEach((button) => {
    button.addEventListener('click', () => {
        if (!assistantMessage) {
            return;
        }

        assistantMessage.value = button.dataset.chatPrompt ?? '';
        assistantMessage.focus();
    });
});

if (voiceButton && assistantMessage && assistantForm) {
    const SpeechRecognition = window.SpeechRecognition ?? window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        voiceButton.disabled = true;
        voiceButton.title = 'El navegador no ofrece reconocimiento de voz.';
        voiceStatus.textContent = 'Voz no disponible';
    } else {
        voiceButton.addEventListener('click', () => {
            const recognition = new SpeechRecognition();
            recognition.lang = 'es';
            recognition.interimResults = false;
            voiceStatus.textContent = 'Escuchando…';

            recognition.addEventListener('result', (event) => {
                assistantMessage.value = event.results[0][0].transcript;
                assistantMessage.focus();
                voiceStatus.textContent = 'Dictado listo; revisa antes de enviar.';
            });

            recognition.addEventListener('error', (event) => {
                voiceStatus.textContent = `No se pudo transcribir: ${event.error}`;
            });

            recognition.addEventListener('end', () => {
                if (voiceStatus.textContent === 'Escuchando…') {
                    voiceStatus.textContent = '';
                }
            });

            recognition.start();
        });
    }
}
