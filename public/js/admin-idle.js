// Reloads an admin tab once the server session has outlived its idle lifetime,
// so an idle tab ends on the sign-in page instead of a stale screen.
(() => {
    const lifetimeMs = Number(document.currentScript.dataset.lifetimeMinutes) * 60 * 1000;
    let lastResponseAt = Date.now();

    const watchLivewire = () => {
        window.Livewire.interceptRequest(({ onResponse }) => {
            onResponse(() => {
                lastResponseAt = Date.now();
            });
        });
    };

    if (window.Livewire) {
        watchLivewire();
    } else {
        document.addEventListener('livewire:init', watchLivewire);
    }

    // An interval, not one long timeout, so a laptop waking from sleep still reloads.
    setInterval(() => {
        if (Date.now() - lastResponseAt > lifetimeMs) {
            window.location.reload();
        }
    }, 15 * 1000);
})();
