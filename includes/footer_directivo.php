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

    function resetSidebar() {
        layout.classList.add("sidebar-collapsed");
        layout.classList.remove("sidebar-open");
        layout.classList.remove("sidebar-hover");
    }

    resetSidebar();

    if (!esMovil()) {
        sidebar.addEventListener("mouseenter", function () {
            layout.classList.add("sidebar-hover");
        });

        sidebar.addEventListener("mouseleave", function () {
            if (layout.classList.contains("sidebar-collapsed")) {
                layout.classList.remove("sidebar-hover");
            }
        });
    }

    menuToggleTop.addEventListener("click", function () {
        if (esMovil()) {
            layout.classList.toggle("sidebar-open");
        } else {
            if (layout.classList.contains("sidebar-collapsed")) {
                layout.classList.remove("sidebar-collapsed");
                layout.classList.add("sidebar-open");
                layout.classList.remove("sidebar-hover");
            } else {
                resetSidebar();
            }
        }
    });

    document.addEventListener("click", function (e) {
        if (esMovil() && layout.classList.contains("sidebar-open")) {
            const clicDentroSidebar = sidebar.contains(e.target);
            const clicBoton = menuToggleTop.contains(e.target);

            if (!clicDentroSidebar && !clicBoton) {
                layout.classList.remove("sidebar-open");
            }
        }
    });

    window.addEventListener("resize", function () {
        resetSidebar();
    });
});
</script>