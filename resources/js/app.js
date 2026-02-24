import './src/select';
import './src/exportUrl';
import './src/identityForm';
import './src/similarItems';
import './src/flash';

// Flash message system
document.addEventListener('livewire:initialized', () => {
    Livewire.on('notify', (data) => {
        // Handle cases where data might be wrapped in an array or passed directly
        const payload = Array.isArray(data) ? data[0] : data;
        const autoClose = payload.autoClose ?? true;
        const duration = payload.duration ?? 4000;

        if (payload.html && window.flashHTML) {
            window.flashHTML(payload.html, payload.type, autoClose, duration);
        } else if (window.flash) {
            window.flash(payload.message, payload.type, autoClose, duration);
        } else {
            console.warn('Flash function not found, message:', payload.message);
        }
    });
});
