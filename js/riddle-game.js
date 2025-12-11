// Riddle Game Logic
document.addEventListener('DOMContentLoaded', function () {
    if (SERVER_STATE.skipPassword) {
        startGame();
    }

    // Setup event listeners
    const passwordInput = document.getElementById('password-input');
    if (passwordInput) {
        passwordInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') checkPasscode();
        });
    }
});

function checkPasscode() {
    const userInput = document.getElementById('password-input').value;
    if (userInput === Config.passcode) {
        document.getElementById('password-screen').style.display = "none";
        document.getElementById('game-container').style.display = "block";
        startGame();
    } else {
        document.getElementById('password-error').textContent = "!!! ACCESS DENIED !!!";
    }
}

function startGame() {
    // Check if pins are already collected to show correct state
    updatePinDisplay();
    // Only fetch new riddle if we don't have one or just starting
    fetchRiddle();
}

// ... Rest of the refactored game logic ...
let answerLength = 0;
let currentCategory = SERVER_STATE.currentCategory || 1;
let collectedPins = SERVER_STATE.collectedPins || [];

function fetchRiddle() {
    fetch("?action=new-riddle")
        .then(response => response.json())
        .then(data => {
            const riddleText = document.getElementById('riddle-text');
            if (riddleText) riddleText.textContent = data.riddle;

            answerLength = data.answerLength;
            currentCategory = data.category;

            // Update active category UI
            document.querySelectorAll('.category-option').forEach(btn => {
                const btnOnClick = btn.getAttribute('onclick');
                if (btnOnClick && btnOnClick.includes(currentCategory)) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            createLetterInputs(answerLength);

            const answerLengthDisplay = document.getElementById('answer-length');
            if (answerLengthDisplay) answerLengthDisplay.textContent = `[REQUIRED LENGTH: ${answerLength}]`;

            const resultDiv = document.getElementById('result');
            if (resultDiv) resultDiv.textContent = '';

            collectedPins = data.pins;
            updatePinDisplay();
        })
        .catch(error => {
            console.error("Error fetching riddle:", error);
            document.getElementById('riddle-text').textContent = 'SYSTEM MALFUNCTION - RETRY';
        });
}

function createLetterInputs(length) {
    const inputContainer = document.getElementById('answer-inputs');
    if (!inputContainer) return;

    inputContainer.innerHTML = '';

    for (let i = 0; i < length; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = 1;
        input.className = 'letter-input';
        input.id = `letter-${i}`;
        input.dataset.index = i;
        inputContainer.appendChild(input);

        // Navigation logic
        input.addEventListener('input', function (e) {
            if (input.value.length === 1 && i < length - 1) {
                document.getElementById(`letter-${i + 1}`).focus();
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && input.value === '' && i > 0) {
                document.getElementById(`letter-${i - 1}`).focus();
            } else if (e.key === 'Enter') {
                submitGuess();
            } else if (e.key === 'ArrowRight' && i < length - 1) {
                document.getElementById(`letter-${i + 1}`).focus();
            } else if (e.key === 'ArrowLeft' && i > 0) {
                document.getElementById(`letter-${i - 1}`).focus();
            }
        });
    }

    if (length > 0) {
        setTimeout(() => {
            const first = document.getElementById('letter-0');
            if (first) first.focus();
        }, 100);
    }
}

function submitGuess() {
    let guess = '';
    for (let i = 0; i < answerLength; i++) {
        const input = document.getElementById(`letter-${i}`);
        guess += input ? (input.value || '') : '';
    }

    if (guess.length !== answerLength) {
        alert(`INPUT MUST BE ${answerLength} CHARACTERS`);
        return;
    }

    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ guess: guess })
    })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('result');
            resultDiv.textContent = data.correct ? "+++ ACCESS GRANTED +++" : "!!! INTRUDER ALERT !!!";

            if (data.correct) {
                // Visualize success
                for (let i = 0; i < answerLength; i++) {
                    const input = document.getElementById(`letter-${i}`);
                    if (input) {
                        input.style.backgroundColor = Config.colors.correct;
                        input.style.color = Config.colors.default;
                        input.disabled = true;
                    }
                }

                if (data.pinAdded) {
                    resultDiv.textContent += " | NEW ACCESS PIN ACQUIRED!";
                    const pinValue = data.pins[data.pins.length - 1];

                    Utils.playNotification(`ACCESS PIN ACQUIRED: ${pinValue}`).then(() => {
                        loadNewRiddle();
                    });
                } else {
                    resultDiv.textContent += " | ALL ACCESS PINS COLLECTED!";
                }

                collectedPins = data.pins;
                updatePinDisplay();

                if (collectedPins.length >= Config.maxPins) {
                    setTimeout(() => {
                        Utils.playNotification(`ALL PINS COLLECTED! PROCEED TO FINAL PHASE!`).then(() => {
                            window.location.href = "?action=mastermind";
                        });
                    }, data.pinAdded ? 2000 : 500);
                }
            } else {
                // Visualize failure
                const inputs = document.querySelectorAll('.letter-input');
                inputs.forEach(input => {
                    input.style.backgroundColor = Config.colors.incorrect;
                    setTimeout(() => {
                        input.style.backgroundColor = Config.colors.default;
                    }, 500);
                });
            }
        });
}

function updatePinDisplay() {
    for (let i = 0; i < Config.maxPins; i++) {
        const slot = document.getElementById(`pin-slot-${i}`);
        if (!slot) continue;

        if (i < collectedPins.length) {
            slot.className = "pin";
            slot.textContent = collectedPins[i];
        } else {
            slot.className = "pin-placeholder";
            slot.textContent = "";
        }
    }

    const nextBtn = document.getElementById('next-phase-button');
    if (nextBtn) {
        nextBtn.style.display = collectedPins.length >= Config.maxPins ? 'block' : 'none';
    }
}

function loadNewRiddle() {
    document.getElementById('riddle-text').textContent = 'ACCESSING RIDDLE DATABASE...';
    fetchRiddle();
}

function resetPins() {
    if (confirm("Are you sure you want to reset your collected pins?")) {
        window.location.href = "?action=reset-pins";
    }
}

function proceedToMastermind() {
    window.location.href = "?action=mastermind";
}
