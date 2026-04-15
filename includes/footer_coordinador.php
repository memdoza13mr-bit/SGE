</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const layout = document.getElementById("layoutPrincipal");
    const sidebar = document.getElementById("sidebarPrincipal");
    const menuToggleTop = document.getElementById("menuToggleTop");

    if (!layout || !sidebar || !menuToggleTop) return;

    function esMovil() {
        return window.innerWidth <= 900;
    }

    function cerrarSidebar() {
        layout.classList.add("sidebar-collapsed");
        layout.classList.remove("sidebar-open");
        layout.classList.remove("sidebar-hover");
    }

    function abrirHover() {
        if (!esMovil() && layout.classList.contains("sidebar-collapsed")) {
            layout.classList.add("sidebar-hover");
        }
    }

    function cerrarHover() {
        if (!esMovil() && layout.classList.contains("sidebar-collapsed")) {
            layout.classList.remove("sidebar-hover");
        }
    }

    cerrarSidebar();

    document.addEventListener("mousemove", function (e) {
        if (esMovil()) return;

        const cercaDelBorde = e.clientX <= 35;
        const dentroZonaIconos = e.clientX <= 95;

        if (layout.classList.contains("sidebar-collapsed")) {
            if (cercaDelBorde || dentroZonaIconos) {
                layout.classList.add("sidebar-hover");
            } else {
                layout.classList.remove("sidebar-hover");
            }
        }
    });

    sidebar.addEventListener("mouseenter", function () {
        abrirHover();
    });

    sidebar.addEventListener("mouseleave", function () {
        cerrarHover();
    });

    menuToggleTop.addEventListener("click", function () {
        if (esMovil()) {
            layout.classList.toggle("sidebar-open");
            layout.classList.remove("sidebar-hover");
        } else {
            if (layout.classList.contains("sidebar-collapsed")) {
                layout.classList.remove("sidebar-collapsed");
                layout.classList.add("sidebar-open");
                layout.classList.remove("sidebar-hover");
            } else {
                cerrarSidebar();
            }
        }
    });

    document.addEventListener("click", function (e) {
        if (esMovil() && layout.classList.contains("sidebar-open")) {
            const clicDentroSidebar = sidebar.contains(e.target);
            const clicBotonMenu = menuToggleTop.contains(e.target);

            if (!clicDentroSidebar && !clicBotonMenu) {
                layout.classList.remove("sidebar-open");
            }
        }
    });

    window.addEventListener("resize", function () {
        cerrarSidebar();
    });
});
</script>