const sidebar = document.querySelector('[data-sidebar-open]');
const sidebarPanel = document.getElementById('app-sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');
const sidebarClose = document.querySelector('[data-sidebar-close]');
const sidebarLinks = document.querySelectorAll('[data-sidebar-link]');

if (sidebar && sidebarPanel && sidebarOverlay) {
    const setSidebarOpen = (isOpen) => {
        sidebarPanel.classList.toggle('-translate-x-full', !isOpen);
        sidebarOverlay.classList.toggle('hidden', !isOpen);
        sidebar.setAttribute('aria-expanded', String(isOpen));
        document.body.classList.toggle('overflow-hidden', isOpen);

        if (isOpen) {
            sidebarClose?.focus();
        } else {
            sidebar.focus();
        }
    };

    sidebar.addEventListener('click', () => setSidebarOpen(true));
    sidebarClose?.addEventListener('click', () => setSidebarOpen(false));
    sidebarOverlay.addEventListener('click', () => setSidebarOpen(false));

    sidebarLinks.forEach((link) => {
        link.addEventListener('click', () => setSidebarOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !sidebarOverlay.classList.contains('hidden')) {
            setSidebarOpen(false);
        }
    });
}
