const GameManager = {
    onGameFinishClicked: function () {
        //Send message to server
        PusherManager.sendMessageToChannel({
            msg: 'Game Finished!',
            gameID: this.GAME_ID
        });
        //
        document.getElementById('game').classList.remove('--show');
        document.getElementById('result').classList.add('--show');
    }
};









var PusherManager = {
CHANNEL_ID: "blockbuster",

pusher: null,
bIsHost: false,
presenceChannel: null,
sUserID: "",
bIsConnected: false,

init: function () {
    Pusher.logToConsole = true;

    this.pusher = new Pusher('34aeee625e438241557b', {
        cluster: 'eu',
        forceTLS: true,
        authEndpoint: 'https://interactionfigure.nl/nhl/blockbusterauth/pusher_auth.php'
    });
},

connectToChannel: function () {
    this.presenceChannel = this.pusher.subscribe('presence-'+this.CHANNEL_ID);
    this.presenceChannel.bind('pusher:subscription_succeeded', this.onSubscriptionSucceeded.bind(this));
},

onSubscriptionSucceeded: function (_data) {
    this.sUserID = _data.myID+"";

    GameConnector.onPusherConnected()
    
    this.presenceChannel.bind('pusher:member_added', this.onMemberAdded.bind(this));
    this.presenceChannel.bind('pusher:member_removed', this.onMemberRemoved.bind(this));
    this.presenceChannel.bind('client-messagetochannel', this.onMessageFromOtherPlayer.bind(this));
},

onMemberAdded: function (_data) {
    console.log('onMemberAdded', _data);
},

onMemberRemoved: function (_data) {
    console.log('onMemberRemoved', _data);
},

sendMessageToChannel: function (_msg) {
    this.presenceChannel.trigger('client-messagetochannel', _msg);
},

onMessageFromOtherPlayer: function (_msg) {
    console.log('onMessageFromOtherPlayer', _msg);
}
};

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