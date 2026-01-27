var stepButton = document.getElementById('stepButton');

function handleButtonClick() {
    alert("Button was clicked! The function ran.");
    console.log("This message is logged to the console.");
}

stepButton.addEventListener('click', handleButtonClick);
