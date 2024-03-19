(function () {
    let menu_tabs = document.querySelectorAll('.phpdebugbar-tab');
    let menu_panels = document.querySelectorAll('.phpdebugbar-panel');
    let minimize_btn = document.querySelector('.phpdebugbar-minimize-btn');
    let maximize_btn = document.querySelector('.phpdebugbar-maximize-btn');
    let close_btn = document.querySelector('.phpdebugbar-close-btn');
    let restore_btn = document.querySelector('.phpdebugbar-restore-btn');
    let panel_body = document.querySelector('.phpdebugbar-body');
    let panel_total = document.querySelector('.phpdebugbar');
    let _body_ = document.querySelector('body');
    let phpdebugbar_resize_handle = document.querySelector('.phpdebugbar-resize-handle');
    let phpdebugbar_drag_capture = document.querySelector('.phpdebugbar-drag-capture');
    let phpdebugbar_margin_bottom = 40;

    let current_tab = localStorage.getItem('php-debug-panel-tab') ?? 'timeline-data';
    let panel_current_height = localStorage.getItem('php-debug-panel-height');
    let panel_maximized = parseInt(localStorage.getItem('php-debug-panel-maximize'));
    let panel_is_hidden = parseInt(localStorage.getItem('php-debug-panel-hidden'));
    let panel_init_height = 300;
    let oldClientY = null;

    addEventListener('mousemove', function(event) {
        if (oldClientY !== null && panel_maximized) {
            let newClientY = event.clientY;
            let deltaY = oldClientY - newClientY;
            let newHeight = parseInt(panel_body.style.height) + deltaY;
            if (newHeight > phpdebugbar_margin_bottom && newHeight < window.innerHeight - phpdebugbar_margin_bottom) {
                panel_body.style.height = newHeight + 'px';
                _body_.style.marginBottom = parseInt(panel_body.style.height) + phpdebugbar_margin_bottom + 'px';
                localStorage.setItem('php-debug-panel-height', panel_body.style.height);
                oldClientY = event.clientY;
            }
        }
    });

    addEventListener("mousedown", function(event) {
        if (event.target.className.indexOf('phpdebugbar-resize-handle') >= 0) {
            oldClientY = event.clientY;
            phpdebugbar_drag_capture.style.display = 'block';
        }
    });

    addEventListener("mouseup", function() {
        oldClientY = null;
        phpdebugbar_drag_capture.style.display = 'none';
    });

    /**
     *
     */
    function minimizePanel()
    {
        minimize_btn.style.display = 'none';
        maximize_btn.style.display = 'block';
        panel_body.style.display = 'none';
        localStorage.setItem('php-debug-panel-maximize', '0');
        _body_.style.marginBottom = phpdebugbar_margin_bottom + 'px';
        phpdebugbar_resize_handle.classList.remove('available');
        panel_maximized = 0;
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
        _body_.style.marginBottom = parseInt(panel_body.style.height) + phpdebugbar_margin_bottom + 'px';
        phpdebugbar_resize_handle.classList.add('available');
        panel_maximized = 1;
    }

    /**
     *
     */
    function hidePanel()
    {
        panel_total.classList.add('phpdebugbar-closed');
        localStorage.setItem('php-debug-panel-hidden', '1');
        _body_.style.marginBottom = phpdebugbar_margin_bottom + 'px';
        phpdebugbar_resize_handle.classList.remove('available');
        panel_is_hidden = 1;
    }

    /**
     *
     */
    function showPanel()
    {
        panel_total.classList.remove('phpdebugbar-closed');
        localStorage.setItem('php-debug-panel-hidden', '0');
        if (panel_maximized) {
            _body_.style.marginBottom = parseInt(panel_body.style.height) + phpdebugbar_margin_bottom + 'px';
            phpdebugbar_resize_handle.classList.add('available');
        }
        panel_is_hidden = 0;
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
    /* init current tab */
    selectMenu(current_tab);
    /* init height for debug panel */
    if (panel_current_height === null) {
        panel_current_height = panel_init_height + 'px';
    }
    panel_body.style.height = panel_current_height;
    _body_.style.marginBottom = parseInt(panel_body.style.height) + phpdebugbar_margin_bottom + 'px';
    /* init maximize or minimize panel */
    if (panel_maximized) {
        maximizePanel();
    } else {
        minimizePanel();
    }
    /* init show or hide panel */
    if (panel_is_hidden) {
        hidePanel();
    } else {
        showPanel();
    }

})();