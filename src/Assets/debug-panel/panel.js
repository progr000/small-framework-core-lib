(function () {
    let menu_tabs = document.querySelectorAll('.phpdebugbar-tab');
    let menu_panels = document.querySelectorAll('.phpdebugbar-panel');
    let minimize_btn = document.querySelector('.phpdebugbar-minimize-btn');
    let maximize_btn = document.querySelector('.phpdebugbar-maximize-btn');
    let close_btn = document.querySelector('.phpdebugbar-close-btn');
    let restore_btn = document.querySelector('.phpdebugbar-restore-btn');
    let panel_body = document.querySelector('.phpdebugbar-body');
    let panel_total = document.querySelector('.phpdebugbar');

    /**
     *
     */
    function minimizePanel()
    {
        minimize_btn.style.display = 'none';
        maximize_btn.style.display = 'block';
        panel_body.style.display = 'none';
        localStorage.setItem('php-debug-panel-maximize', '0');
    }

    /**
     *
     */
    function maximizePanel()
    {
        maximize_btn.style.display = 'none';
        minimize_btn.style.display = 'block';
        panel_body.style.display = 'block';
        localStorage.setItem('php-debug-panel-maximize', '1');
    }

    /**
     *
     */
    function hidePanel()
    {
        panel_total.classList.add('phpdebugbar-closed');
        localStorage.setItem('php-debug-panel-hidden', '1');
    }

    /**
     *
     */
    function showPanel()
    {
        panel_total.classList.remove('phpdebugbar-closed');
        localStorage.setItem('php-debug-panel-hidden', '0');
    }

    /**
     * function for switch tab
     * @param {string} tab
     */
    function selectMenu(tab)
    {
        localStorage.setItem('php-debug-panel-tab', tab);
        menu_tabs.forEach(function (tab) {
            tab.classList.remove('phpdebugbar-active');
        });
        menu_panels.forEach(function (panel) {
            panel.classList.remove('phpdebugbar-active');
        });
        document.querySelectorAll(`.js-${tab}`).forEach(function (selected) {
            selected.classList.add('phpdebugbar-active');
        })
    }

    minimize_btn.onclick = function () {
        minimizePanel();
    };
    maximize_btn.onclick = function () {
        maximizePanel();
    };
    close_btn.onclick = function () {
        hidePanel();
    };
    restore_btn.onclick = function () {
        showPanel();
    };

    /* switch tab on click by menu element */
    menu_tabs.forEach(function (menu_tab) {
        menu_tab.onclick = function () {
            selectMenu(this.dataset.tab);
            maximizePanel();
        }
    });

    /* on load page restore tab selected before */
    let current_tab = localStorage.getItem('php-debug-panel-tab') ?? 'timeline-data';
    selectMenu(current_tab);
    let panel_maximized = parseInt(localStorage.getItem('php-debug-panel-maximize'));
    if (panel_maximized) {
        maximizePanel();
    } else {
        minimizePanel();
    }

    let panel_is_hidden = parseInt(localStorage.getItem('php-debug-panel-hidden'));
    if (panel_is_hidden) {
        hidePanel();
    } else {
        showPanel();
    }

})();