class Toast
{
    constructor(options = {})
    {
        this.options = Object.assign({
            'displayTime': 3000,
            'fadeTime': 2000,
        }, options);
    }

    success(message, title = false, options = {})
    {
        this._show('success', message, title, options);
    }

    info(message, title = false, options = {})
    {
        this._show('info', message, title, options);
    }

    warning(message, title = false, options = {})
    {
        this._show('warning', message, title, options);
    }

    error(message, title = false, options = {})
    {
        this._show('error', message, title, options);
    }

    _show(type, message, title, options)
    {
        let that = this;

        options = Object.assign({}, this.options, options);

        let toastRoot = document.getElementById('toast-root');
        if (toastRoot === null) {
            toastRoot = document.createElement('div');
            toastRoot.id = 'toast-root';
            document.body.appendChild(toastRoot);
        }

        let toast = document.createElement('div');
        toast.classList.add('toast', type);

        let toastContent = document.createElement('div');
        toastContent.classList.add('toast-content');
        toast.appendChild(toastContent);

        if (title !== false) {
            let toastTitle = document.createElement('h3');
            toastTitle.innerHTML = title;
            toastContent.appendChild(toastTitle);
        }

        let toastMessage = document.createElement('p');
        toastMessage.innerHTML = message;
        toastContent.appendChild(toastMessage);

        toastRoot.appendChild(toast);

        toast.addEventListener('click', function(e) {
            e.currentTarget.removeToast();
        });

        toast.removeToast = function() {
            toast.remove();

            if (toastRoot.querySelector('.toast') === null) {
                toastRoot.remove();
            }
        };

        toast.startFade = function() {
            toast.classList.add('fading');
            toast.style.transitionDuration = that.options.fadeTime / 1000 + 's';
        };

        let fading, removing;
        fading = setTimeout(toast.startFade, options.displayTime);
        removing = setTimeout(toast.removeToast, options.displayTime + options.fadeTime);

        toast.addEventListener('mouseover', function(e) {
            clearTimeout(fading);
            clearTimeout(removing);
            e.currentTarget.classList.remove('fading');
            e.currentTarget.style.transitionDuration = '';
        });

        toast.addEventListener('mouseout', function(e) {
            fading = setTimeout(toast.startFade, options.displayTime);
            removing = setTimeout(toast.removeToast, options.displayTime + options.fadeTime);
        });
    }
}

export default new Toast();
