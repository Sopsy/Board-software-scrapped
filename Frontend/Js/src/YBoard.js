import YQuery from './YQuery';
import Tooltip from './Tooltip';
import Toast from './Toast';
import Captcha from './Captcha';

import Theme from './YBoard/Theme';
import Catalog from './YBoard/Catalog';
import Thread from './YBoard/Thread';
import Post from './YBoard/Post';
import PostForm from './YBoard/PostForm';
import Notifications from './YBoard/Notifications';

class YBoard
{
    constructor()
    {
        let that = this;
        new Catalog();
        this.Theme = new Theme();
        this.Thread = new Thread();
        this.Post = new Post();
        this.PostForm = new PostForm();
        this.Notifications = new Notifications();
        this.messagePreviewCache = {};

        if (this.isBadBrowser()) {
            this.browserWarning();
        }

        document.addEventListener('keydown', function(e)
        {
            if (!e.ctrlKey && !e.shiftKey && e.which === 116 || e.ctrlKey && !e.shiftKey && e.which === 82) {
                // Make F5 || CTRL + R function like clicking links and thus not reloading everything
                // Maybe we can remove this completely one day.
                e.preventDefault();
                that.pageReload();
            }
            if (e.ctrlKey && e.which === 13) {
                // Submit the post form with CTRL + Enter
                that.PostForm.submit(e);
            }
        });

        YQuery.on('submit', 'form.ajax', function(event)
        {
            that.submitForm(event);
        });

        // Sidebar signup & login
        let loginForm = document.getElementById('login');
        if (loginForm !== null) {
            loginForm.querySelector('.e-signup').addEventListener('click', function(e)
            {
                that.signup(e, true);
            });
            document.getElementById('signup').querySelector('.cancel').addEventListener('click', function(e)
            {
                that.signup(e, false);
            });
        }

        // Go to top
        document.getElementById('scroll-to-top').addEventListener('click', function()
        {
            window.scrollTo(0, 0);
        });

        // Go to bottom
        document.getElementById('scroll-to-bottom').addEventListener('click', function()
        {
            window.scrollTo(0, document.body.scrollHeight);
        });

        // Reload page
        document.getElementById('reload-page').addEventListener('click', function()
        {
            that.pageReload();
        });

        this.initElement(document);
    }

    initElement(elm)
    {
        let that = this;

        elm.querySelectorAll('.datetime').forEach(this.localizeDatetime);
        elm.querySelectorAll('.number').forEach(this.localizeNumber);
        elm.querySelectorAll('.currency').forEach(this.localizeCurrency);
        let tooltips = elm.querySelectorAll('.tip, .ref');

        this.PostForm.bindPostEvents(elm);
        this.Post.bindEvents(elm);

        tooltips.forEach(function(elm) {
            that.addTooltipEventListener(elm, that);
        });
    }

    addTooltipEventListener(elm, that)
    {
        elm.addEventListener('mouseover', tooltipOpen);

        function tooltipOpen(e) {
            let postId = null;
            if (typeof e.target.dataset.id !== 'undefined') {
                postId = e.target.dataset.id;
            }
            let postXhr = null;
            let cached = typeof that.messagePreviewCache[postId] !== 'undefined';
            new Tooltip(e, {
                'openDelay': !cached ? 50 : 0,
                'position': 'bottom',
                'content': cached ? that.messagePreviewCache[postId] : that.spinnerHtml(),
                'onOpen': !cached ? opened : null,
                'onClose': !cached ? closed : null,
            });

            function opened(tip)
            {

                postXhr = YQuery.post('/api/post/get', {'postId': postId}, {'errorFunction': null}).onLoad(
                    ajaxLoaded).onError(ajaxError);
                postXhr = postXhr.getXhrObject();

                function ajaxLoaded(xhr)
                {
                    // Close and reopen to reflow the contents
                    // For some reason by just setting the contents it stays really narrow
                    if (tip.elm !== null) {
                        tip.close();
                    }
                    tip = new Tooltip(tip.event, {
                        'openDelay': 0,
                        'position': tip.options.position,
                        'content': xhr.responseText,
                        'onOpen': function(tip) {
                            tip.elm.style.willChange = 'contents';

                            that.initElement(tip.elm);

                            let referringPost = e.target.closest('.post');
                            if (referringPost !== null) {
                                let reflinkInTip = tip.elm.querySelector(
                                    '.message .ref[data-id="' + referringPost.dataset.id + '"]');
                                if (reflinkInTip !== null) {
                                    reflinkInTip.classList.add('referring');
                                }
                            }
                            tip.elm.style.willChange = '';
                            that.messagePreviewCache[postId] = tip.getContent();
                        },
                    });
                }

                function ajaxError(xhr)
                {
                    if (xhr.responseText.length !== 0) {
                        let json = JSON.parse(xhr.responseText);
                        tip.setContent(json.message);
                    } else {
                        tip.setContent(messages.errorOccurred);
                    }
                }
            }
            function closed()
            {
                if (postXhr !== null && postXhr.readyState !== 4) {
                    postXhr.abort();
                }
            }
        }
    }

    localizeDatetime(elm)
    {
        elm.innerHTML = new Date(elm.innerHTML.replace(' ', 'T') + 'Z').toLocaleString();
    }

    localizeNumber(elm)
    {
        elm.innerHTML = parseFloat(elm.innerHTML).toLocaleString(undefined, {
            minimumFractionDigits: 0,
        });
    }

    localizeCurrency(elm, currency = 'eur')
    {
        // I think this is a bug with Babel?
        if (currency === 0) {
            currency = 'eur';
        }

        elm.innerHTML = parseFloat(elm.innerHTML).toLocaleString(undefined, {
            'style': 'currency',
            'currency': currency,
        });
    }

    getSelectionText()
    {
        let text = '';
        if (window.getSelection) {
            text = window.getSelection().toString();
        } else {
            if (document.selection && document.selection.type !== 'Control') {
                text = document.selection.createRange().text;
            }
        }

        return text.trim();
    }

    isBadBrowser()
    {
        if (typeof FormData !== 'function') {
            return true;
        }

        if (typeof localStorage !== 'object') {
            return true;
        }

        return false;
    }

    browserWarning()
    {
        let browserWarning = document.createElement('div');
        browserWarning.classList.add('old-browser-warning');
        browserWarning.innerHTML = '<p>' + messages.oldBrowserWarning + '</p>';

        document.body.appendChild(browserWarning);
    }

    pageReload()
    {
        window.location = window.location.href.split('#')[0];
    }

    returnToBoard()
    {
        // Remove everything after the last slash and redirect
        // Should work if we are in a thread, otherwise not really
        let url = window.location.href;
        url = url.substr(0, url.lastIndexOf('/') + 1);

        window.location = url;
    }

    spinnerHtml(classes = '')
    {
        if (classes !== '') {
            classes += ' ';
        }

        return '<span class="' + classes + 'loading icon-loading spin"></span>';
    }

    submitForm(e, form = false, captchaResponse = false)
    {
        if (e !== null) {
            e.preventDefault();
            form = e.target;
        }

        if (captchaResponse === false && typeof form.captcha !== 'undefined') {
            // Validate captchas
            form.captcha.execute();

            return;
        }

        if (form === false) {
            return false;
        }

        if (typeof form.dataset.action === 'undefined') {
            return false;
        }

        let fd = new FormData(form);
        if (captchaResponse !== false) {
            fd.append('captchaResponse', captchaResponse);
        }

        let overlay = document.createElement('div');
        overlay.classList.add('form-overlay');
        overlay.innerHTML = '<div>' + this.spinnerHtml() + '</div></div>';
        form.appendChild(overlay);

        YQuery.post(form.dataset.action, fd).onLoad(function(xhr)
        {
            let data = JSON.parse(xhr.responseText);
            if (data.reload) {
                if (data.url) {
                    window.location = data.url;
                } else {
                    window.location.reload();
                }
            } else {
                overlay.remove();
                Toast.success(data.message);
                form.reset();
            }
        }).onError(function()
        {
            overlay.remove();
        }).onEnd(function()
        {
            if (typeof form.captcha !== 'undefined') {
                form.captcha.reset();
            }
        });
    }

    signup(e, show)
    {
        // Signup form in sidebar
        let that = this;
        e.preventDefault();

        let loginForm = document.getElementById('login');
        let signupForm = document.getElementById('signup');

        if (show) {
            signupForm.show('flex');
            loginForm.hide();

            signupForm.captcha = new Captcha(signupForm.querySelector('.captcha'), {
                'size': 'invisible',
                'callback': function(response)
                {
                    that.submitForm(null, signupForm, response);
                },
            });
        } else {
            signupForm.hide();
            loginForm.show('flex');
        }
    }
}

export default new YBoard();
