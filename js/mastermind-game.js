// Mastermind Game Logic
let attempts = 0;
const maxAttempts = 5;
const guessInputContainer = document.getElementById('guess-inputs');
const resultDiv = document.getElementById('result');

// Constants from server
const secretCode = SECRET_PINS.join('');
const codeLength = secretCode.length;

document.addEventListener('DOMContentLoaded', () => {
    generateInputBoxes(codeLength);
});

function generateInputBoxes(length) {
    if (!guessInputContainer) return;

    guessInputContainer.innerHTML = '';
    for (let i = 0; i < length; i++) {
        let input = document.createElement('input');
        input.type = 'text';
        input.maxLength = 1;
        input.id = `digit${i}`;
        input.classList.add('digit-input');
        input.inputMode = "numeric";
        guessInputContainer.appendChild(input);
    }
    setupInputListeners();

    // Focus first input
    if (length > 0) {
        setTimeout(() => document.getElementById('digit0').focus(), 100);
    }
}

function setupInputListeners() {
    const inputs = document.querySelectorAll('.digit-input');
    inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            // Ensure only numbers if that's the rule, or pins can be anything? 
            // Pins seem to be digits 0-9 from game_functions.php
            input.value = input.value.replace(/\D/g, '');

            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && input.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
            if (event.key === 'Enter') {
                submitMastermindGuess();
            }
        });
    });
}

function submitMastermindGuess() {
    let guess = "";
    for (let i = 0; i < codeLength; i++) {
        const val = document.getElementById(`digit${i}`).value || '';
        guess += val;
    }

    if (guess.length !== codeLength) {
        resultDiv.textContent = `ENTER A ${codeLength}-DIGIT CODE`;
        return;
    }

    const feedback = checkGuess(guess, secretCode);
    const isCorrect = updateInputFeedback(feedback, guess);
    attempts++;

    if (isCorrect) {
        resultDiv.textContent = 'ACCESS GRANTED! CODE UNLOCKED!';

        // Success animation or redirect
        setTimeout(() => {
            window.location.href = "success.php";
        }, 1500);

        // Disable input
        document.getElementById('guess-inputs').style.display = 'none';

    } else if (attempts >= maxAttempts) {
        resultDiv.textContent = `GAME OVER! THE CODE WAS: ${secretCode}`;
        // Maybe offer a reset or back button after a delay
        setTimeout(() => {
            if (confirm("GAME OVER. RETRY FINAL PHASE?")) {
                location.reload();
            } else {
                window.location.href = "?action=reset-game";
            }
        }, 2000);
    } else {
        resultDiv.textContent = `ATTEMPT ${attempts} of ${maxAttempts} | INCORRECT SEQUENCE`;
    }
}

function checkGuess(guess, code) {
    // Basic Mastermind logic: Green = correct pos, Yellow = correct digit wrong pos, Gray = wrong
    const feedback = [];
    const codeArr = code.split('');
    const guessArr = guess.split('');
    const usedCodeIndices = new Set();
    const usedGuessIndices = new Set();

    // Pass 1: exact matches (Green)
    for (let i = 0; i < codeLength; i++) {
        if (guessArr[i] === codeArr[i]) {
            feedback[i] = 'green';
            usedCodeIndices.add(i);
            usedGuessIndices.add(i);
        } else {
            feedback[i] = null; // Pending
        }
    }

    // Pass 2: wrong position (Yellow)
    for (let i = 0; i < codeLength; i++) {
        if (feedback[i] !== null) continue; // Already handled

        const guessChar = guessArr[i];
        // Look for this char in code, anywhere that hasn't been matched
        let found = false;
        for (let j = 0; j < codeLength; j++) {
            if (!usedCodeIndices.has(j) && codeArr[j] === guessChar) {
                feedback[i] = 'yellow';
                usedCodeIndices.add(j);
                found = true;
                break;
            }
        }

        if (!found) {
            feedback[i] = 'gray';
        }
    }

    return feedback;
}

function updateInputFeedback(feedback, guess) {
    const allCorrect = feedback.every(f => f === 'green');
    feedback.forEach((status, index) => {
        const input = document.getElementById(`digit${index}`);
        input.classList.remove('green', 'yellow', 'gray');
        input.classList.add(status);
        // We don't change value, keep what they typed
    });
    return allCorrect;
}
