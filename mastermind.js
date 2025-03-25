const secretCode = "260";
let attempts = 0;
let maxAttempts = 5;
const guessInputContainer = document.getElementById('guess-inputs');
const resultDiv = document.getElementById('result');

function generateInputBoxes(length) {
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
}

function setupInputListeners() {
    const inputs = document.querySelectorAll('.digit-input');
    inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '');
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && input.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });
}

function submitGuess() {
    let guess = "";
    for (let i = 0; i < secretCode.length; i++) {
        guess += document.getElementById(`digit${i}`).value || '';
    }
    if (guess.length !== secretCode.length) {
        resultDiv.textContent = `ENTER A ${secretCode.length}-DIGIT CODE`;
        return;
    }
    
    const feedback = checkGuess(guess, secretCode);
    const isCorrect = updateInputFeedback(feedback, guess);
    attempts++;
    if (isCorrect) {
        resultDiv.textContent = 'ACCESS GRANTED! CODE UNLOCKED!';
    } else if (attempts >= maxAttempts) {
        resultDiv.textContent = `GAME OVER! THE CODE WAS: ${secretCode}`;
    } else {
        resultDiv.textContent = `ATTEMPT ${attempts} of ${maxAttempts}`;
    }
}

function checkGuess(guess, code) {
    return Array.from(guess).map((digit, index) => digit === code[index] ? 'green' : code.includes(digit) ? 'yellow' : 'gray');
}

function updateInputFeedback(feedback, guess) {
    const allCorrect = feedback.every(f => f === 'green');
    feedback.forEach((status, index) => {
        const input = document.getElementById(`digit${index}`);
        input.classList.remove('green', 'yellow', 'gray');
        input.classList.add(status);
        input.value = guess[index];
        input.disabled = allCorrect || status === 'green';
    });
    return allCorrect;
}

generateInputBoxes(secretCode.length);