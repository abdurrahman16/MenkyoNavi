import Alpine from 'alpinejs';
import { initMotion } from './motion';

window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', () => {
    initMotion();
});

document.addEventListener('livewire:navigated', () => {
    initMotion();
});

Alpine.start();
