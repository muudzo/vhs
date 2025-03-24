const secretCode ="2608";
let attempts = 0;
let maxAttempts = 5;

const guessInputContainer = document.getElementById('guess-input-container');
const resultdiv = document.getElementById('result');

//inner html for input boxes 
function generateInputBoxes(length){
    guessInputContainer.innerHTML = '';
     
    const inputsHTML = Array.from({length}, (_, i) => 
    `<input type="text" maxLength="1" id="digit${i}" class="digit-input" pattern="\\d" inputMode="numeric">`
    ).join('');
    guessInputContainer.innerHTML = inputsHTML;
//event listerners for inputs
    setupInputListeners();
}

function setupInputListeners() {
    const inputs = document.querySelectorAll('#guess-inputs .digit-input');
    
    inputs.forEach((input, index) => {
      // Auto focus on next input when a digit is entered
      input.addEventListener('input', (event) => {
        // Ensure only digits are allowed
        input.value = input.value.replace(/\D/g, '');
        
        if (input.value.length === 1 && index < inputs.length - 1) {
          inputs[index + 1].focus();
        }
      });
      // Allow backspace to go to previous input
      input.addEventListener('keydown', (event) => {
        if (event.key === 'Backspace' && input.value === '' && index > 0) {
          inputs[index - 1].focus();
        }
      });
    });
    // Add event listener for enter key to submit
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        submitGuess();
      }
    });
  }

  //check input  vs saved pin 
  function checkGuess(guess,code) {
    const codeArr = code.split('');
    const guessArr = guess.split('');
    const feedback = Array(codeArr.length).fill('gray');
    const codeCopy = [...codeArr];

    //first check correct digit and correct position
    guessArr.forEach((digit, index) => {
        if(digit === codeArr[index]){
            feedback[index] = 'green';
            codeCopy[index] = null;
        }
    });
    //second check right digit wrong position
    guessArr.forEach((digit, index) => {
        if (feedback[index] !== 'green') {
          const foundIndex = codeCopy.indexOf(digit);
          if (foundIndex !== -1) {
            feedback[index] = 'yellow';
            codeCopy[foundIndex] = null; // Mark as used
          }
        }
      });
      
      return feedback;
    }
  
    function updateInputFeedback(feedback, guess) {
        const allCorrect = feedback.every(f => f === 'green');

        feedback.forEach((status, index) => {
            const input = document.getElementById(`digit${index}`);
            
            // Remove previous feedback classes
            input.classList.remove('green', 'yellow', 'gray');
            input.classList.add(status);
            input.value = guess[index];
            
            // If game is over or the digit is correct, disable the input
            input.disabled = allCorrect || status === 'green';
          });
          
          return allCorrect;
        }
        
        // Process the user's guess.
        function submitGuess() {
          const guess = Array.from({ length: secretCode.length }, (_, i) => 
            document.getElementById(`digit${i}`).value || ''
          ).join('');
          
          if (guess.length !== secretCode.length) {
            resultDiv.textContent = `ENTER A ${secretCode.length}-DIGIT CODE`;
            return;
          }
          
          const feedback = checkGuess(guess, secretCode);
          const isCorrect = updateInputFeedback(feedback, guess);
          attempts++;
          
          if (isCorrect) {
            resultDiv.textContent = 'ACCESS GRANTED! CODE UNLOCKED!';
            disableAllInputs(true);
          } else if (attempts >= maxAttempts) {
            resultDiv.textContent = `GAME OVER! THE CODE WAS: ${secretCode}`;
            disableAllInputs(true);
          } else {
            resultDiv.textContent = `ATTEMPT ${attempts} of ${maxAttempts}`;
            resetIncorrectInputs();
          }
        }
        
        // Helper function to disable/enable all inputs
        function disableAllInputs(disabled) {
          const inputs = document.querySelectorAll('#guess-inputs .digit-input');
          inputs.forEach(input => {
            input.disabled = disabled;
          });
        }
        
        // Helper function to reset incorrect inputs
        function resetIncorrectInputs() {
          const inputs = document.querySelectorAll('#guess-inputs .digit-input:not(.green)');
          inputs.forEach(input => {
            input.value = '';
          });
          
          // Focus on the first empty input
          const firstEmpty = document.querySelector('#guess-inputs .digit-input:not([disabled])');
          if (firstEmpty) {
            firstEmpty.focus();
          }
        }
        
        // Initial setup.
        generateInputBoxes(secretCode.length);