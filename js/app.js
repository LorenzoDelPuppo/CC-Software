        // Seleziona il campo nascosto
        const hairInput = document.getElementById("hair");

        // Seleziona i bottoni
        const lunghiButton = document.getElementById("lunghi");
        const cortiButton = document.getElementById("corti");

        // Event listener per il bottone "Lunghi"
        lunghiButton.addEventListener("click", () => {
            hairInput.value = "lunghi"; // Imposta il valore del campo nascosto
            alert("Hai selezionato: Lunghi"); // Feedback opzionale
        });

        // Event listener per il bottone "Corti"
        cortiButton.addEventListener("click", () => {
            hairInput.value = "corti"; // Imposta il valore del campo nascosto
            alert("Hai selezionato: Corti"); // Feedback opzionale
        });

        // Verifica prima dell'invio
        const hairForm = document.querySelector("form");
        hairForm.addEventListener("submit", (event) => {
            if (hairInput.value === "") {
                event.preventDefault(); // Blocca l'invio del form
                alert("Seleziona una lunghezza dei capelli prima di inviare.");
            }
        });