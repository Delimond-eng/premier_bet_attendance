import "./bootstrap";

verifySchedule();

function verifySchedule() {
    fetch("/schedules.verify")
        .then((response) => response.json())
        .then((data) => console.log("Vérification planning effectuée:", data))
        .catch((error) =>
            console.error("Erreur lors de la vérification:", error)
        );
}
