document.addEventListener("DOMContentLoaded", function(event) {
    const message = document.querySelector('#message');

    // Abonne-toi au canal de notifications
    window.Echo.channel('notification')
        .listen('.PatrolNotificationEvent', (e) => {
            // Utilise la fonction Text-to-Speech pour lire les notifications
            speakText(`${e.data.title}, ${e.data.content}`);
            if (message) {
                message.textContent = JSON.stringify(e);
            }
        });
    // Fonction pour le Text-to-Speech
    function speakText(text) {
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'fr-FR';  // Langue française
            window.speechSynthesis.speak(utterance);
        } else {
            alert("Votre navigateur ne supporte pas la synthèse vocale.");
        }
    }
});
