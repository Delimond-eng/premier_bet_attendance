/* import Echo from "../libs/echo.min.js";

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: "asldkfjs", // Remplacez par la clé réelle de votre Pusher ou WebSocket
    cluster: 'mt1', // Si vous utilisez Pusher, sinon supprimez-le pour WebSockets
    wsHost: window.location.hostname, // 127.0.0.1 si vous testez en local
    wsPort: 6001, // Port par défaut pour Laravel WebSockets
    forceTLS: false, // Utilisez false si vous êtes en local, sinon true pour la prod
    disableStats: true, // Empêche l'envoi de statistiques à Pusher
    enabledTransports: ['ws', 'wss'], // Active les WebSockets
});

setTimeout(() => {
    window.Echo.channel('notification')
        .listen('.PatrolNotitificationEvent', (e) => {
            console.log(e);
        }).error((error) => {
            console.error('Error:', error);
        });
}, 1000); */