document.addEventListener("DOMContentLoaded", function(event) {
    const currentUserId = document.querySelector('meta[name="user-id"]').getAttribute('content'); // Récupère l'ID utilisateur depuis une balise meta
    window.Echo.channel('talkie-walkie')
        .listen('.audio.sent', (e) => {
            console.log("status", e.sender)
            // Vérifier si l'utilisateur courant est différent de l'émetteur
            if (e.sender === 'app') {
                playAudio(e.audioUrl, e.userId);
            } else {
                //playAudio('assets/audios/walkie-talkie-off.mp3')
                console.log("L'émetteur ne s'écoute pas.");
            }
        });

    function playAudio(audioUrl, audioId) {
        const audio = new Audio(audioUrl);
        audio.play();

        // Écouter la fin de la lecture de l'audio
        audio.onended = function() {
            new Audio("assets/audios/walkie-talkie-off.mp3").play();
            //deleteAudioFile(audioId);
        };
    }

    function deleteAudioFile(audioId) {
        fetch(`/delete-track/${audioId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Fin de transmission...');
                } else {
                    console.error('Échec de la suppression');
                }
            })
            .catch(error => console.error('Erreur:', error));
    }
});
