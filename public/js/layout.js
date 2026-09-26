document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');

    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }

    function applyTheme(theme) {
        const finalTheme = theme === 'auto'
            ? getSystemTheme()
            : theme;

        html.setAttribute('data-bs-theme', finalTheme);

        if (themeIcon) {
            if (theme === 'auto') {
                themeIcon.className = 'fa-solid fa-circle-half-stroke';
            } else if (finalTheme === 'dark') {
                themeIcon.className = 'fa-solid fa-moon';
            } else {
                themeIcon.className = 'fa-solid fa-sun';
            }
        }
    }

    const savedTheme = localStorage.getItem('admin-theme') || 'light';
    applyTheme(savedTheme);

    document.querySelectorAll('.theme-option').forEach(button => {
        button.addEventListener('click', function () {
            const theme = this.dataset.theme;
            localStorage.setItem('admin-theme', theme);
            applyTheme(theme);
        });
    });

    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', function () {
            const saved = localStorage.getItem('admin-theme');
            if (saved === 'auto') {
                applyTheme('auto');
            }
        });

    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');

    function toggleMobileSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }

    mobileMenuBtn?.addEventListener('click', toggleMobileSidebar);
    overlay?.addEventListener('click', toggleMobileSidebar);

    const collapseButtons = document.querySelectorAll('[data-sidebar-collapse-toggle]');

    function setCollapsed(collapsed) {
        sidebar.classList.toggle('collapsed', collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');

        collapseButtons.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = collapsed
                    ? 'fa-solid fa-angles-right'
                    : 'fa-solid fa-angles-left';
            }
        });
    }

    if (window.matchMedia('(min-width: 992px)').matches) {
        setCollapsed(localStorage.getItem('sidebar-collapsed') === '1');
    }

    collapseButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            setCollapsed(!sidebar.classList.contains('collapsed'));
        });
    });
});