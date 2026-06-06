import {
    Livewire,
    Alpine,
} from "../../vendor/livewire/livewire/dist/livewire.esm";
import TomSelect from "tom-select";

window.TomSelect = TomSelect;
window.debounce = (callback, wait) => {
    let timeoutId = null;
    return (...args) => {
        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(() => {
            callback(...args);
        }, wait);
    };
};

// Livewire alert
Livewire.on("alert", (params) => {
    Flux.toast({
        heading: params.title ?? params.type ?? "Success",
        text: params.message,
        variant: params.type ?? "success",
    });
});

// Livewire confirmation dialog
Livewire.on("confirm", (params) => {
    Flux.modal('confirm').show();

    const titleElement = document.getElementById('confirm-title');
    const messageElement = document.getElementById('confirm-message');
    const buttonElement = document.getElementById('confirm-button');

    // Update modal content
    if (titleElement) {
        titleElement.textContent = params.title ?? "Are you sure?";
    }
    if (messageElement) {
        messageElement.textContent = params.message ?? `You won't be able to revert this`;
    }
    if (buttonElement) {
        buttonElement.textContent = params.confirmText ?? "Yes";
    }

    // Dispatch Livewire event when button is clicked
    buttonElement.onclick = () => {
        Livewire.dispatch(params.function, { id: params.id });
        Flux.modal('confirm').close();
    };
});

// Livewire setValueById
Livewire.on("setValueById", (params) => {
    setTimeout(() => {
        document.getElementById(params.id).value = params.value;
    }, 500);
});

// Livewire toast
Livewire.on("toast", (params) => {
    Flux.toast({
        heading: params.title ?? params.type ?? "Success",
        text: params.message,
        variant: params.type ?? "success",
    });
});

Livewire.start();
