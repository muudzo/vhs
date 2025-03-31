<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ACCESS GRANTED</title>
  <link rel="stylesheet" href="mastermind.css">  
  <style>
    @keyframes textShadowPulse {
      0% { text-shadow: 0 0 5px #0f0, 0 0 10px #0f0; }
      50% { text-shadow: 0 0 15px #0f0, 0 0 25px #0f0, 0 0 35px #0f0; }
      100% { text-shadow: 0 0 5px #0f0, 0 0 10px #0f0; }
    }
    
    @keyframes crtFlicker {
      0% { opacity: 0.98; }
      25% { opacity: 1; }
      30% { opacity: 0.9; }
      35% { opacity: 1; }
      70% { opacity: 0.99; }
      75% { opacity: 0.9; }
      76% { opacity: 1; }
      100% { opacity: 0.98; }
    }
    
    @keyframes glitch {
      0% { transform: translate(0); }
      20% { transform: translate(-2px, 2px); }
      40% { transform: translate(-2px, -2px); }
      60% { transform: translate(2px, 2px); }
      80% { transform: translate(2px, -2px); }
      100% { transform: translate(0); }
    }
    
    @keyframes pixelate {
      0% { filter: none; }
      15% { filter: blur(1px); }
      16% { filter: none; }
      45% { filter: none; }
      46% { filter: blur(1px); }
      48% { filter: none; }
      100% { filter: none; }
    }
    
    .success-container {
      text-align: center;
      padding: 40px;
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      animation: crtFlicker 6s infinite, pixelate 8s infinite;
      overflow: hidden;
      position: relative;
    }
    
    .success-title {
      font-size: 3rem;
      margin-bottom: 30px;
      animation: textShadowPulse 2s infinite, glitch 3s infinite;
    }
    
    .reward-code {
      font-size: 2rem;
      margin: 20px 0;
      padding: 20px;
      background-color: #001100;
      display: inline-block;
      border: 3px dashed #0f0;
      animation: textShadowPulse 2s infinite;
      font-family: 'Courier New', monospace;
      font-weight: bold;
      letter-spacing: 2px;
    }
    
    .success-message {
      font-size: 1.2rem;
      margin: 20px 0;
      max-width: 600px;
      line-height: 1.6;
    }
    
    .arcade-button {
      background: #000;
      border: 3px solid #0f0;
      color: #0f0;
      padding: 15px 30px;
      font-size: 1.2rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin: 20px;
      font-family: 'Courier New', monospace;
      text-transform: uppercase;
      position: relative;
      overflow: hidden;
    }
    
    .arcade-button:hover {
      background: #0f0;
      color: #000;
      animation: textShadowPulse 1s infinite;
    }
    
    .arcade-button:before {
      content: '';
      position: absolute;
      top: -10px;
      left: -10px;
      right: -10px;
      bottom: -10px;
      border: 2px dashed #0f0;
      animation: textShadowPulse 2s infinite;
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .arcade-button:hover:before {
      opacity: 1;
    }
    
    .scanline {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(
        to bottom,
        rgba(0, 0, 0, 0),
        rgba(0, 255, 0, 0.1),
        rgba(0, 0, 0, 0)
      );
      pointer-events: none;
      opacity: 0.7;
      z-index: 9;
      animation-name: scanline;
      animation-duration: 7s;
      animation-timing-function: linear;
      animation-iteration-count: infinite;
    }
    
    @keyframes scanline {
      0% { transform: translateY(-100%); }
      100% { transform: translateY(100%); }
    }
    
    .stars {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 8;
      overflow: hidden;
    }
    
    .star {
      position: absolute;
      background-color: #0f0;
      width: 2px;
      height: 2px;
      border-radius: 50%;
      opacity: 0;
      animation-name: starAnimation;
      animation-timing-function: linear;
      animation-iteration-count: infinite;
    }
    
    @keyframes starAnimation {
      0% {
        opacity: 0;
        transform: scale(0);
      }
      50% {
        opacity: 1;
        transform: scale(1.5);
      }
      100% {
        opacity: 0;
        transform: scale(0);
      }
    }
    
    /* Typography with cyberpunk vibes */
    h1, h2, h3, p {
      font-family: 'Courier New', monospace;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    
    /* Add some glitch effect to images or icons */
    .glitch-icon {
      display: inline-block;
      margin: 20px;
      animation: glitch 2s infinite;
    }
  </style>
</head>
<body>
  <div class="success-container">
    <div class="scanline"></div>
    <div class="stars" id="stars-container"></div>
    
    <h1 class="success-title">ACCESS GRANTED</h1>
    
    <div class="glitch-icon">
      <svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="45" stroke="#0f0" stroke-width="3"/>
        <path d="M30 50 L45 65 L70 35" stroke="#0f0" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    
    <p class="success-message">YOU HAVE SUCCESSFULLY BROKEN THE CODE AND ACCESSED THE MAINFRAME.</p>
    
    <div class="reward-code">
      REWARD: BLOCKBUSTER80
    </div>
    
    <p class="success-message">USE THIS CODE TO CLAIM YOUR PRIZE OR ACCESS THE NEXT LEVEL.</p>
    
    <div class="button-container">
      <button class="arcade-button" onclick="window.location.href='?'">PLAY AGAIN</button>
      <button class="arcade-button" onclick="window.location.href='/'">MAIN MENU</button>
    </div>
  </div>
  
  <script>
    // Create star animation
    function createStars() {
      const starsContainer = document.getElementById('stars-container');
      const numberOfStars = 100;
      
      for (let i = 0; i < numberOfStars; i++) {
        const star = document.createElement('div');
        star.classList.add('star');
        
        // Random position
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        
        // Random size
        const size = Math.random() * 3 + 1;
        
        // Random duration
        const duration = Math.random() * 3 + 2;
        
        // Random delay
        const delay = Math.random() * 5;
        
        star.style.left = `${x}%`;
        star.style.top = `${y}%`;
        star.style.width = `${size}px`;
        star.style.height = `${size}px`;
        star.style.animationDuration = `${duration}s`;
        star.style.animationDelay = `${delay}s`;
        
        starsContainer.appendChild(star);
      }
    }
    
    // Terminal typing effect
    function typeText(elementId, text, speed) {
      const element = document.getElementById(elementId);
      let i = 0;
      
      function type() {
        if (i < text.length) {
          element.textContent += text.charAt(i);
          i++;
          setTimeout(type, speed);
        }
      }
      
      element.textContent = "";
      type();
    }
    
    // Run animations on page load
    document.addEventListener('DOMContentLoaded', function() {
      createStars();
      
      // Optional: Add audio effect
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      
      function playSuccessSound() {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.type = 'square';
        oscillator.frequency.value = 440;
        gainNode.gain.value = 0.1;
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.start();
        
        // Frequency modulation
        oscillator.frequency.exponentialRampToValueAtTime(880, audioContext.currentTime + 0.2);
        oscillator.frequency.exponentialRampToValueAtTime(1760, audioContext.currentTime + 0.4);
        
        // End sound
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 1);
        setTimeout(() => {
          oscillator.stop();
        }, 1000);
      }
      
      playSuccessSound();
    });
  </script>
</body>
</html>