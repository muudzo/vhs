// Game Configuration
const Config = {
    passcode: '1234',
    maxPins: 4,
    apiEndpoint: 'retro.php',
    colors: {
        correct: '#0f0',
        incorrect: '#300',
        default: '#000',
        text: '#0f0'
    }
};

// Utils
const Utils = {
    playNotification: (message) => {
        const notification = document.getElementById('pin-notification');
        if (!notification) return;
        
        notification.textContent = message;
        notification.style.display = 'block';
        
        // Return promise that resolves when notification hides
        return new Promise(resolve => {
            setTimeout(() => {
                notification.style.display = 'none';
                resolve();
            }, 2000);
        });
    }
};
