import YQuery from '../YQuery';

class Theme
{
    constructor()
    {
        let that = this;

        // Switch theme
        document.querySelector('.e-switch-theme').addEventListener('click', function()
        {
            that.switchVariation();
        });

        // Mobile, click on the shadow to hide
        document.getElementById('sidebar').addEventListener('click', function (e) {
            if (e.offsetX > document.getElementById('sidebar').clientWidth) {
                document.body.classList.toggle('sidebar-visible');
            }
        });

        document.querySelector('#e-sidebar-toggle').addEventListener('click', function () {
            document.body.classList.toggle('sidebar-visible');
        });

        // Hide sidebar
        document.getElementById('e-sidebar-hide').addEventListener('click', function()
        {
            that.toggleSidebar();
        });
    }

    toggleSidebar()
    {
        document.body.classList.toggle('no-sidebar');
        let hide = document.body.classList.contains('no-sidebar');
        if (hide) {
            document.body.classList.remove('sidebar-visible');
        }

        YQuery.post('/api/user/preferences/set', {
            'hideSidebar': hide,
        });
    }

    switchVariation()
    {
        let css = document.querySelectorAll('head .css');
        css = css[css.length - 1];

        let light = css.dataset.light;
        let dark = css.dataset.dark;
        let currentIsDark = css.dataset.darktheme === 'true';

        let newVariation;
        let darkTheme;
        if (currentIsDark) {
            newVariation = light;
            darkTheme = false;
        } else {
            newVariation = dark;
            darkTheme = true;
        }

        let newCss = css.cloneNode();

        newCss.setAttributes({
            'href': newVariation,
            'data-darkTheme': darkTheme,
        });
        newCss.appendAfter(css);

        let timeout = setTimeout(function()
        {
            css.remove();
        }, 5000);

        YQuery.post('/api/user/preferences/set', {
            'darkTheme': darkTheme,
        });
    }
}

export default Theme;
