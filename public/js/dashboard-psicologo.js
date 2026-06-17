document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('modo-escucha');
    const status = document.getElementById('modo-escucha-status');

    if (!toggle) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const endpoint = toggle.dataset.url;

    toggle.addEventListener('change', async () => {
        if (!endpoint) return;

        const previous = !toggle.checked;
        toggle.disabled = true;

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ modo_escucha_activo: toggle.checked }),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'No se pudo actualizar el Modo Escucha.');
            }

            toggle.checked = Boolean(data.modo_escucha_activo);
            if (status) {
                status.textContent = toggle.checked ? 'Disponible para auxilio' : 'No disponible';
            }
        } catch (error) {
            toggle.checked = previous;
            if (status) {
                status.textContent = toggle.checked ? 'Disponible para auxilio' : 'No disponible';
            }
            alert(error.message || 'No se pudo actualizar el Modo Escucha.');
        } finally {
            toggle.disabled = false;
        }
    });
});
