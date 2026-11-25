/*ESCODER O SUBMENU*/

document.addEventListener('DOMContentLoaded', function () {
    const menu = document.querySelector('#toplevel_page_smtt-intranet');
    if (!menu) return;

    const submenu = menu.querySelector('.wp-submenu');
    if (submenu) {
        submenu.style.display = 'none';
        submenu.style.opacity = '0';
        submenu.style.visibility = 'hidden';
        submenu.style.pointerEvents = 'none';
    }

    menu.addEventListener('mouseenter', function () {
        if (submenu) {
            submenu.style.display = 'none';
            submenu.style.opacity = '0';
            submenu.style.visibility = 'hidden';
            submenu.style.pointerEvents = 'none';
        }
    });
});