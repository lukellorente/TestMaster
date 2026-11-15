// Aplica el tema antes de pintar para evitar el destello blanco (archivo externo por la CSP).
(function () {
    var t = null;
    try { t = localStorage.getItem('theme'); } catch (e) {}
    if (t !== 'dark' && t !== 'light') {
        t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.dataset.theme = t;
})();
