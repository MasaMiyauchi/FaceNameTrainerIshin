/**
 * Toggle the visibility of an element
 * 
 * @param {string} id The ID of the element to toggle
 */
function toggleElement(id) {
    const element = document.getElementById(id);
    if (element) {
        if (element.style.display === 'none') {
            element.style.display = 'block';
            document.getElementById(`toggle-${id}`).textContent = '[-] Hide Details';
        } else {
            element.style.display = 'none';
            document.getElementById(`toggle-${id}`).textContent = '[+] Show Details';
        }
    }
}

/**
 * Initialize all toggleable elements
 */
function initToggleElements() {
    const toggleables = document.querySelectorAll('[data-toggleable]');
    toggleables.forEach(element => {
        element.style.display = 'none';
        const id = element.id;
        const toggleButton = document.getElementById(`toggle-${id}`);
        if (toggleButton) {
            toggleButton.textContent = '[+] Show Details';
        }
    });
}

document.addEventListener('DOMContentLoaded', initToggleElements);
