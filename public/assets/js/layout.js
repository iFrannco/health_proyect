(function () {
    var STORAGE_KEY = 'layout.sidebar.collapsed';

    function saveState(collapsed) {
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? 'true' : 'false');
        } catch (error) {
            /* ignore */
        }
    }

    function initSidebarPersistence() {
        if (!window.jQuery) {
            return;
        }

        var $document = window.jQuery(document);

        $document.on('collapsed.lte.pushmenu', function () {
            saveState(true);
        });

        $document.on('shown.lte.pushmenu', function () {
            saveState(false);
        });

        saveState(document.body.classList.contains('sidebar-collapse'));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarPersistence);
    } else {
        initSidebarPersistence();
    }
})();
