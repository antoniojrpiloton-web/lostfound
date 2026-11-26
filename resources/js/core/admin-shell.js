const STORAGE_KEY = 'blf.admin.sidebarCollapsed';

export function initAdminShell() {
    const shell = document.querySelector('[data-admin-shell]');
    if (!shell) {
        return;
    }

    const toggleButton = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const searchInput = document.querySelector('[data-global-search]');
    const desktopQuery = window.matchMedia('(min-width: 992px)');

    if (desktopQuery.matches && window.localStorage.getItem(STORAGE_KEY) === '1') {
        shell.classList.add('is-collapsed');
    }

    const closeMobileSidebar = () => {
        shell.classList.remove('is-mobile-open');
        toggleButton?.setAttribute('aria-expanded', 'false');
    };

    toggleButton?.addEventListener('click', () => {
        if (desktopQuery.matches) {
            const collapsed = shell.classList.toggle('is-collapsed');
            window.localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
            toggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        } else {
            const open = shell.classList.toggle('is-mobile-open');
            toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    });

    backdrop?.addEventListener('click', closeMobileSidebar);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && shell.classList.contains('is-mobile-open')) {
            closeMobileSidebar();
        }

        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k' && searchInput) {
            event.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });
}
