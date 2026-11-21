import './bootstrap';
import Alpine from 'alpinejs';

function registerComponents(alpineInstance) {
    alpineInstance.data('countUp', (target, decimals = 0, prefix = '') => ({
        display: prefix + (decimals > 0 ? (0).toFixed(decimals) : '0'),
        init() {
            const end = parseFloat(target) || 0;
            const duration = 650;
            const start = performance.now();

            const format = (val) => {
                const formatted = val.toLocaleString('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals,
                });
                return prefix + formatted;
            };

            if (end === 0) {
                this.display = format(0);
                return;
            }

            const tick = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                this.display = format(end * eased);
                if (progress < 1) {
                    requestAnimationFrame(tick);
                } else {
                    this.display = format(end);
                }
            };

            requestAnimationFrame(tick);
        },
    }));
}

document.addEventListener('alpine:init', () => {
    registerComponents(window.Alpine);
});

// Only boot standalone Alpine on non-Livewire pages (Livewire 4 bundles & starts Alpine automatically)
if (!window.Livewire && !window.Alpine) {
    window.Alpine = Alpine;
    registerComponents(Alpine);
    Alpine.start();
}
