// Purpose: Mastermind game logic and Pusher integration;
let attempts = 0;
let maxAttempts = 5;
const guessInputContainer = document.getElementById('guess-inputs');
const resultDiv = document.getElementById('result');

// Pusher Manager (Handles Connection & Messaging)
var PusherManager = {
    CHANNEL_ID: "blockbuster",
    pusher: null,
    presenceChannel: null,
    sUserID: "",

    init: function () {
        Pusher.logToConsole = true;

        this.pusher = new Pusher('34aeee625e438241557b', {
            cluster: 'eu',
            forceTLS: true,
            authEndpoint: 'https://interactionfigure.nl/nhl/blockbusterauth/pusher_auth.php'
        });

        this.connectToChannel();
    },

    connectToChannel: function () {
        this.presenceChannel = this.pusher.subscribe('presence-' + this.CHANNEL_ID);
        this.presenceChannel.bind('pusher:subscription_succeeded', this.onSubscriptionSucceeded.bind(this));
    },

    onSubscriptionSucceeded: function (_data) {
        this.sUserID = _data.myID + "";
        this.presenceChannel.bind('client-messagetochannel', this.onMessageFromOtherPlayer.bind(this));
    },

    sendMessageToChannel: function (_msg) {
        if (this.presenceChannel) {
            this.presenceChannel.trigger('client-messagetochannel', _msg);
            console.log("Message sent:", _msg);
        } else {
            console.warn("Pusher channel not ready");
        }
    },

    onMessageFromOtherPlayer: function (_msg) {
        console.log('Received message:', _msg);
    }
};

// Game play (Handles Gameplay & Pusher Message Trigger)
const GameManager = {
    onGameFinishClicked: function () {
        PusherManager.sendMessageToChannel({
            msg: 'Game Finished!',
            gameID: "12345"
        });

        document.getElementById('game').classList.remove('--show');
        document.getElementById('result').classList.add('--show');
    }
};

// Initialize Pusher Connection
PusherManager.init();

//  Generate Input Fields
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

//  Set Up Input Behavior
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

//  Check & Process Guess
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
        
        // Trigger Pusher message on win
        PusherManager.sendMessageToChannel({
            msg: 'Game Finished!',
            gameID: "12345"
        });

        // Disable further input
        document.getElementById('guess-inputs').style.display = 'none';

    } else if (attempts >= maxAttempts) {
        resultDiv.textContent = `GAME OVER! THE CODE WAS: ${secretCode}`;
    } else {
        resultDiv.textContent = `ATTEMPT ${attempts} of ${maxAttempts}`;
    }
}

//  Compare Guess to Secret Code
function checkGuess(guess, code) {
    return Array.from(guess).map((digit, index) => digit === code[index] ? 'green' : code.includes(digit) ? 'yellow' : 'gray');
}

// Update Input Feedback
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

//  Start the game
generateInputBoxes(secretCode.length);
