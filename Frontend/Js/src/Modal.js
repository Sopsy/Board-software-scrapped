class Modal
{
    constructor(options = {})
    {
        let that = this;
        if (typeof window.modals === 'undefined') {
            window.modals = [];
        }

        let modal = {};
        modal.options = Object.assign({
            'content': null,
            'title': null,
            'onOpen': null,
            'onClose': null,
        }, options);

        // This is global, create the modal root if it does not exist
        this.modalRoot = document.getElementById('modal-root');
        if (this.modalRoot === null) {
            this.modalRoot = document.createElement('div');
            this.modalRoot.id = 'modal-root';

            // Bind closing click to container
            this.modalRoot.addEventListener('click', function(e)
            {
                if (e.currentTarget === e.target) {
                    that.closeLatest();
                }
            });

            // Bind esc to close
            document.addEventListener('keydown', keyDownListener);

            document.body.classList.add('modal-open');
            document.body.appendChild(this.modalRoot);
        }

        function keyDownListener(e)
        {
            if (e.keyCode !== 27) {
                return;
            }

            that.closeLatest();
        }
        modal.close = function()
        {
            if (typeof modal.options.onClose === 'function') {
                modal.options.onClose(this);
            }

            modal.elm.remove();

            if (that.modalRoot.querySelector('.modal') === null) {
                document.removeEventListener('keydown', keyDownListener);
                that.modalRoot.remove();
                document.body.classList.remove('modal-open');
            }
        };

        modal.setTitle = function(title)
        {
            modal.options.title = title;
            modal.titleTextElm.innerHTML = title;
        };

        modal.setContent = function(content)
        {
            modal.content.innerHTML = content;
        };

        // Create modal element
        modal.elm = document.createElement('div');
        modal.elm.classList.add('modal');
        this.modalRoot.appendChild(modal.elm);

        // Create close button
        modal.closeButton = document.createElement('button');
        modal.closeButton.classList.add('close', 'icon-cross');
        modal.closeButton.addEventListener('click', modal.close);

        // Create title element, if needed
        if (modal.options.title !== null) {
            modal.titleElm = document.createElement('div');
            modal.titleElm.classList.add('title');
            modal.elm.appendChild(modal.titleElm);

            modal.titleTextElm = document.createElement('span');
            modal.titleTextElm.innerHTML = modal.options.title;
            modal.titleElm.appendChild(modal.titleTextElm);

            modal.titleElm.appendChild(modal.closeButton);
        } else {
            modal.elm.appendChild(modal.closeButton);
        }

        // Create element for the content
        modal.content = document.createElement('div');
        modal.content.classList.add('content');
        modal.content.innerHTML = modal.options.content;
        modal.elm.appendChild(modal.content);

        if (typeof modal.options.onOpen === 'function') {
            modal.options.onOpen(modal);
        }

        window.modals.push(modal);

        return modal;
    }

    closeLatest()
    {
        let latest = window.modals.pop();
        if (typeof latest !== 'undefined') {
            latest.close();
        }
    }
}

export default Modal;
