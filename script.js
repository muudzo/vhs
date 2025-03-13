let answerLength = 0;

window.onload = fetchRiddle;

function fetchRiddle() {
    fetch("?action=new-riddle")
        .then(response => {
            if (!response.ok) throw new Error('NETWORK FAILURE');
            return response.json();
        })
        .then(data => {
            document.getElementById('riddle-text').textContent = data.riddle;
            answerLength = data.answerLength;
            const guessInput = document.getElementById('guess-input');
            guessInput.maxLength = answerLength;
            guessInput.placeholder = `ENTER ${answerLength}-CHARACTER SEQUENCE`;
            document.getElementById('answer-length').textContent = `[REQUIRED LENGTH: ${answerLength}]`;
            document.getElementById('result').textContent = '';
            guessInput.value = '';
        })
        .catch(error => {
            console.error('ERROR:', error);
            document.getElementById('riddle-text').textContent = 'SYSTEM MALFUNCTION - RETRY';
        });
}

function loadNewRiddle() {
    document.getElementById('riddle-text').textContent = 'ACCESSING RIDDLE DATABASE...';
    fetchRiddle();
}

function submitGuess() {
    const guess = document.getElementById('guess-input').value;
    
    if (guess.trim().length !== answerLength) {
        alert(`INPUT MUST BE ${answerLength} CHARACTERS`);
        return;
    }
    
    fetch("", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ guess: guess })
    })
    .then(response => {
        if (!response.ok) throw new Error("TRANSMISSION ERROR");
        return response.json();
    })
    .then(data => {
        document.getElementById('result').textContent = data.correct 
            ? "+++ ACCESS GRANTED +++" 
            : "!!! INTRUDER ALERT !!!";
    })
    .catch(error => {
        console.error("ERROR:", error);
        document.getElementById('result').textContent = "SYSTEM ERROR";
    });
}