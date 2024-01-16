<?php defined('ABSPATH') || exit; ?>

<script type="module" defer>
    import Alpine from 'https://cdn.skypack.dev/pin/alpinejs@v3.10.2-bcx0dLVVrB09MQMPHogv/mode=imports,min/optimized/alpinejs.js';
    window.Alpine = Alpine;

    document.addEventListener("DOMContentLoaded", async () => {
        // Wait for Alpine.js to load and ready
        await new Promise(resolve => {
            let waiting = setInterval(() => {
                if (
                    !window.hasOwnProperty('deferAlpine') ||
                    window.deferAlpine === 0
                ) {
                    clearInterval(waiting);
                    resolve();
                }
            }, 1000);
        });

        window.Alpine.start();
    });
</script>