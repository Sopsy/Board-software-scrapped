/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// identity function for calling harmony imports with the correct context
/******/ 	__webpack_require__.i = function(value) { return value; };
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 7);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var YQuery = function () {
    function YQuery() {
        _classCallCheck(this, YQuery);

        this.ajaxOptions = {
            'method': 'GET',
            'url': '',
            'data': null,
            'timeout': 0,
            'loadFunction': null,
            'timeoutFunction': null,
            'errorFunction': null,
            'loadendFunction': null,
            'cache': false,
            'contentType': null,
            'xhr': null
        };
        this.ajaxHeaders = {
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    YQuery.prototype.on = function on(eventName, target, fn) {
        document.addEventListener(eventName, function (event) {
            if (!target || event.target.matches(target)) {
                fn(event);
            }
        });

        return this;
    };

    // AJAX

    YQuery.prototype.ajaxSetup = function ajaxSetup(options) {
        var headers = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

        this.ajaxOptions = Object.assign(this.ajaxOptions, options);
        this.ajaxHeaders = Object.assign(this.ajaxHeaders, headers);
    };

    YQuery.prototype.get = function get(url) {
        var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
        var headers = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

        options = Object.assign({
            'url': url
        }, options);

        headers = Object.assign({}, this.ajaxHeaders, headers);

        return this.ajax(options, headers);
    };

    YQuery.prototype.post = function post(url) {
        var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
        var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
        var headers = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};

        options = Object.assign({
            'method': 'POST',
            'url': url,
            'data': data
        }, options);

        headers = Object.assign({}, this.ajaxHeaders, headers);

        return this.ajax(options, headers);
    };

    YQuery.prototype.ajax = function ajax() {
        var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
        var headers = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

        options = Object.assign({}, this.ajaxOptions, options);

        if (!options.cache) {
            headers = Object.assign(headers, {
                'Cache-Control': 'no-cache, max-age=0'
            });
        }

        var xhr = new XMLHttpRequest();
        xhr.timeout = options.timeout;
        xhr.open(options.method, options.url);

        for (var key in headers) {
            if (!headers.hasOwnProperty(key)) {
                continue;
            }

            xhr.setRequestHeader(key, headers[key]);
        }

        // Run always
        this.onEnd = function (fn) {
            xhr.addEventListener('loadend', function () {
                fn(xhr);
            });

            return this;
        };
        if (typeof options.loadendFunction === 'function') {
            this.onEnd(options.loadendFunction);
        }

        // OnLoad
        this.onLoad = function (fn) {
            xhr.addEventListener('load', function () {
                if (xhr.status !== 200) {
                    return;
                }

                fn(xhr);
            });

            return this;
        };
        if (typeof options.loadFunction === 'function') {
            this.onLoad(options.loadFunction);
        }

        // OnTimeout
        this.onTimeout = function (fn) {
            xhr.addEventListener('timeout', function () {
                fn(xhr);
            });

            return this;
        };
        if (typeof options.timeoutFunction === 'function') {
            this.onTimeout(options.timeoutFunction);
        }

        // OnError
        this.onError = function (fn) {
            xhr.addEventListener('loadend', function () {
                if (xhr.status === 200 || xhr.status === 0) {
                    return;
                }
                fn(xhr);
            });

            return this;
        };
        if (typeof options.errorFunction === 'function') {
            this.onError(options.errorFunction);
        }

        this.getXhrObject = function () {
            return xhr;
        };

        // Customizable XHR-object
        if (typeof options.xhr === 'function') {
            xhr = options.xhr(xhr);
        }

        if (typeof options.data.append !== 'function') {
            // Not FormData... Maybe a regular object? Convert to FormData.
            var data = new FormData();
            Object.keys(options.data).forEach(function (key) {
                return data.append(key, options.data[key]);
            });
            options.data = data;
        }

        xhr.send(options.data);

        return this;
    };

    YQuery.prototype.benchmark = function benchmark(fn, iterations) {
        if (typeof iterations === 'undefined') {
            iterations = 100000;
        }
        var iterationCount = iterations;

        var start = new Date();
        while (iterations--) {
            fn();
        }
        var totalTime = new Date() - start;
        var msPerOp = totalTime / iterationCount;
        var opsPerSec = (1000 / msPerOp).toFixed(2);

        return totalTime + ' ms, ' + msPerOp.toFixed(2) + ' ms per op, ' + opsPerSec + ' ops/sec';
    };

    return YQuery;
}();

exports.default = new YQuery();

/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

var _Tooltip = __webpack_require__(8);

var _Tooltip2 = _interopRequireDefault(_Tooltip);

var _Toast = __webpack_require__(2);

var _Toast2 = _interopRequireDefault(_Toast);

var _Captcha = __webpack_require__(4);

var _Captcha2 = _interopRequireDefault(_Captcha);

var _Theme = __webpack_require__(14);

var _Theme2 = _interopRequireDefault(_Theme);

var _Catalog = __webpack_require__(9);

var _Catalog2 = _interopRequireDefault(_Catalog);

var _Thread = __webpack_require__(15);

var _Thread2 = _interopRequireDefault(_Thread);

var _Post = __webpack_require__(11);

var _Post2 = _interopRequireDefault(_Post);

var _PostForm = __webpack_require__(12);

var _PostForm2 = _interopRequireDefault(_PostForm);

var _Notifications = __webpack_require__(10);

var _Notifications2 = _interopRequireDefault(_Notifications);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var YBoard = function () {
    function YBoard() {
        _classCallCheck(this, YBoard);

        var that = this;
        new _Catalog2.default();
        this.Theme = new _Theme2.default();
        this.Thread = new _Thread2.default();
        this.Post = new _Post2.default();
        this.PostForm = new _PostForm2.default();
        this.Notifications = new _Notifications2.default();
        this.messagePreviewCache = {};

        if (this.isBadBrowser()) {
            this.browserWarning();
        }

        document.addEventListener('keydown', function (e) {
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

        _YQuery2.default.on('submit', 'form.ajax', function (event) {
            that.submitForm(event);
        });

        // Sidebar signup & login
        var loginForm = document.getElementById('login');
        if (loginForm !== null) {
            loginForm.querySelector('.e-signup').addEventListener('click', function (e) {
                that.signup(e, true);
            });
            document.getElementById('signup').querySelector('.cancel').addEventListener('click', function (e) {
                that.signup(e, false);
            });
        }

        // Go to top
        document.getElementById('scroll-to-top').addEventListener('click', function () {
            window.scrollTo(0, 0);
        });

        // Go to bottom
        document.getElementById('scroll-to-bottom').addEventListener('click', function () {
            window.scrollTo(0, document.body.scrollHeight);
        });

        // Reload page
        document.getElementById('reload-page').addEventListener('click', function () {
            that.pageReload();
        });

        this.initElement(document);
    }

    YBoard.prototype.initElement = function initElement(elm) {
        var that = this;

        elm.querySelectorAll('.datetime').forEach(this.localizeDatetime);
        elm.querySelectorAll('.number').forEach(this.localizeNumber);
        elm.querySelectorAll('.currency').forEach(this.localizeCurrency);
        var tooltips = elm.querySelectorAll('.tip, .ref');

        this.PostForm.bindPostEvents(elm);
        this.Post.bindEvents(elm);

        tooltips.forEach(function (elm) {
            that.addTooltipEventListener(elm, that);
        });
    };

    YBoard.prototype.addTooltipEventListener = function addTooltipEventListener(elm, that) {
        elm.addEventListener('mouseover', tooltipOpen);

        function tooltipOpen(e) {
            var postId = null;
            if (typeof e.target.dataset.id !== 'undefined') {
                postId = e.target.dataset.id;
            }
            var postXhr = null;
            var cached = typeof that.messagePreviewCache[postId] !== 'undefined';
            new _Tooltip2.default(e, {
                'openDelay': !cached ? 50 : 0,
                'position': 'bottom',
                'content': cached ? that.messagePreviewCache[postId] : that.spinnerHtml(),
                'onOpen': !cached ? opened : null,
                'onClose': !cached ? closed : null
            });

            function opened(tip) {

                postXhr = _YQuery2.default.post('/api/post/get', { 'postId': postId }, { 'errorFunction': null }).onLoad(ajaxLoaded).onError(ajaxError);
                postXhr = postXhr.getXhrObject();

                function ajaxLoaded(xhr) {
                    // Close and reopen to reflow the contents
                    // For some reason by just setting the contents it stays really narrow
                    if (tip.elm !== null) {
                        tip.close();
                    }
                    tip = new _Tooltip2.default(tip.event, {
                        'openDelay': 0,
                        'position': tip.options.position,
                        'content': xhr.responseText,
                        'onOpen': function onOpen(tip) {
                            tip.elm.style.willChange = 'contents';

                            that.initElement(tip.elm);

                            var referringPost = e.target.closest('.post');
                            if (referringPost !== null) {
                                var reflinkInTip = tip.elm.querySelector('.message .ref[data-id="' + referringPost.dataset.id + '"]');
                                if (reflinkInTip !== null) {
                                    reflinkInTip.classList.add('referring');
                                }
                            }
                            tip.elm.style.willChange = '';
                            that.messagePreviewCache[postId] = tip.getContent();
                        }
                    });
                }

                function ajaxError(xhr) {
                    if (xhr.responseText.length !== 0) {
                        var json = JSON.parse(xhr.responseText);
                        tip.setContent(json.message);
                    } else {
                        tip.setContent(messages.errorOccurred);
                    }
                }
            }
            function closed() {
                if (postXhr !== null && postXhr.readyState !== 4) {
                    postXhr.abort();
                }
            }
        }
    };

    YBoard.prototype.localizeDatetime = function localizeDatetime(elm) {
        elm.innerHTML = new Date(elm.innerHTML.replace(' ', 'T') + 'Z').toLocaleString();
    };

    YBoard.prototype.localizeNumber = function localizeNumber(elm) {
        elm.innerHTML = parseFloat(elm.innerHTML).toLocaleString(undefined, {
            minimumFractionDigits: 0
        });
    };

    YBoard.prototype.localizeCurrency = function localizeCurrency(elm) {
        var currency = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'eur';

        // I think this is a bug with Babel?
        if (currency === 0) {
            currency = 'eur';
        }

        elm.innerHTML = parseFloat(elm.innerHTML).toLocaleString(undefined, {
            'style': 'currency',
            'currency': currency
        });
    };

    YBoard.prototype.getSelectionText = function getSelectionText() {
        var text = '';
        if (window.getSelection) {
            text = window.getSelection().toString();
        } else {
            if (document.selection && document.selection.type !== 'Control') {
                text = document.selection.createRange().text;
            }
        }

        return text.trim();
    };

    YBoard.prototype.isBadBrowser = function isBadBrowser() {
        if (typeof FormData !== 'function') {
            return true;
        }

        if ((typeof localStorage === 'undefined' ? 'undefined' : _typeof(localStorage)) !== 'object') {
            return true;
        }

        return false;
    };

    YBoard.prototype.browserWarning = function browserWarning() {
        var browserWarning = document.createElement('div');
        browserWarning.classList.add('old-browser-warning');
        browserWarning.innerHTML = '<p>' + messages.oldBrowserWarning + '</p>';

        document.body.appendChild(browserWarning);
    };

    YBoard.prototype.pageReload = function pageReload() {
        window.location = window.location.href.split('#')[0];
    };

    YBoard.prototype.returnToBoard = function returnToBoard() {
        // Remove everything after the last slash and redirect
        // Should work if we are in a thread, otherwise not really
        var url = window.location.href;
        url = url.substr(0, url.lastIndexOf('/') + 1);

        window.location = url;
    };

    YBoard.prototype.spinnerHtml = function spinnerHtml() {
        var classes = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';

        if (classes !== '') {
            classes += ' ';
        }

        return '<span class="' + classes + 'loading icon-loading spin"></span>';
    };

    YBoard.prototype.submitForm = function submitForm(e) {
        var form = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
        var captchaResponse = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

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

        var fd = new FormData(form);
        if (captchaResponse !== false) {
            fd.append('captchaResponse', captchaResponse);
        }

        var overlay = document.createElement('div');
        overlay.classList.add('form-overlay');
        overlay.innerHTML = '<div>' + this.spinnerHtml() + '</div></div>';
        form.appendChild(overlay);

        _YQuery2.default.post(form.dataset.action, fd).onLoad(function (xhr) {
            var data = JSON.parse(xhr.responseText);
            if (data.reload) {
                if (data.url) {
                    window.location = data.url;
                } else {
                    window.location.reload();
                }
            } else {
                overlay.remove();
                _Toast2.default.success(data.message);
                form.reset();
            }
        }).onError(function () {
            overlay.remove();
        }).onEnd(function () {
            if (typeof form.captcha !== 'undefined') {
                form.captcha.reset();
            }
        });
    };

    YBoard.prototype.signup = function signup(e, show) {
        // Signup form in sidebar
        var that = this;
        e.preventDefault();

        var loginForm = document.getElementById('login');
        var signupForm = document.getElementById('signup');

        if (show) {
            signupForm.show('flex');
            loginForm.hide();

            signupForm.captcha = new _Captcha2.default(signupForm.querySelector('.captcha'), {
                'size': 'invisible',
                'callback': function callback(response) {
                    that.submitForm(null, signupForm, response);
                }
            });
        } else {
            signupForm.hide();
            loginForm.show('flex');
        }
    };

    return YBoard;
}();

exports.default = new YBoard();

/***/ }),
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Toast = function () {
    function Toast() {
        var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

        _classCallCheck(this, Toast);

        this.options = Object.assign({
            'displayTime': 3000,
            'fadeTime': 2000
        }, options);
    }

    Toast.prototype.success = function success(message) {
        var title = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
        var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

        this._show('success', message, title, options);
    };

    Toast.prototype.info = function info(message) {
        var title = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
        var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

        this._show('info', message, title, options);
    };

    Toast.prototype.warning = function warning(message) {
        var title = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
        var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

        this._show('warning', message, title, options);
    };

    Toast.prototype.error = function error(message) {
        var title = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
        var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

        this._show('error', message, title, options);
    };

    Toast.prototype._show = function _show(type, message, title, options) {
        var that = this;

        options = Object.assign({}, this.options, options);

        var toastRoot = document.getElementById('toast-root');
        if (toastRoot === null) {
            toastRoot = document.createElement('div');
            toastRoot.id = 'toast-root';
            document.body.appendChild(toastRoot);
        }

        var toast = document.createElement('div');
        toast.classList.add('toast', type);

        var toastContent = document.createElement('div');
        toastContent.classList.add('toast-content');
        toast.appendChild(toastContent);

        if (title !== false) {
            var toastTitle = document.createElement('h3');
            toastTitle.innerHTML = title;
            toastContent.appendChild(toastTitle);
        }

        var toastMessage = document.createElement('p');
        toastMessage.innerHTML = message;
        toastContent.appendChild(toastMessage);

        toastRoot.appendChild(toast);

        toast.addEventListener('click', function (e) {
            e.currentTarget.removeToast();
        });

        toast.removeToast = function () {
            toast.remove();

            if (toastRoot.querySelector('.toast') === null) {
                toastRoot.remove();
            }
        };

        toast.startFade = function () {
            toast.classList.add('fading');
            toast.style.transitionDuration = that.options.fadeTime / 1000 + 's';
        };

        var fading = void 0,
            removing = void 0;
        fading = setTimeout(toast.startFade, options.displayTime);
        removing = setTimeout(toast.removeToast, options.displayTime + options.fadeTime);

        toast.addEventListener('mouseover', function (e) {
            clearTimeout(fading);
            clearTimeout(removing);
            e.currentTarget.classList.remove('fading');
            e.currentTarget.style.transitionDuration = '';
        });

        toast.addEventListener('mouseout', function (e) {
            fading = setTimeout(toast.startFade, options.displayTime);
            removing = setTimeout(toast.removeToast, options.displayTime + options.fadeTime);
        });
    };

    return Toast;
}();

exports.default = new Toast();

/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Modal = function () {
    function Modal() {
        var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

        _classCallCheck(this, Modal);

        var that = this;
        if (typeof window.modals === 'undefined') {
            window.modals = [];
        }

        var modal = {};
        modal.options = Object.assign({
            'content': null,
            'title': null,
            'onOpen': null,
            'onClose': null
        }, options);

        // This is global, create the modal root if it does not exist
        this.modalRoot = document.getElementById('modal-root');
        if (this.modalRoot === null) {
            this.modalRoot = document.createElement('div');
            this.modalRoot.id = 'modal-root';

            // Bind closing click to container
            this.modalRoot.addEventListener('click', function (e) {
                if (e.currentTarget === e.target) {
                    that.closeLatest();
                }
            });

            // Bind esc to close
            document.addEventListener('keydown', keyDownListener);

            document.body.classList.add('modal-open');
            document.body.appendChild(this.modalRoot);
        }

        function keyDownListener(e) {
            if (e.keyCode !== 27) {
                return;
            }

            that.closeLatest();
        }
        modal.close = function () {
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

        modal.setTitle = function (title) {
            modal.options.title = title;
            modal.titleTextElm.innerHTML = title;
        };

        modal.setContent = function (content) {
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

    Modal.prototype.closeLatest = function closeLatest() {
        var latest = window.modals.pop();
        if (typeof latest !== 'undefined') {
            latest.close();
        }
    };

    return Modal;
}();

exports.default = Modal;

/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Captcha = function () {
    function Captcha(elm) {
        var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

        _classCallCheck(this, Captcha);

        if (typeof grecaptcha === 'undefined' || !elm || elm.innerHTML.length !== 0) {
            // Captcha not enabled, grecaptcha -library not loaded, captcha element not found or already rendered
            return;
        }

        options = Object.assign({ 'sitekey': config.reCaptchaPublicKey }, options);
        this.widgetId = grecaptcha.render(elm, options);

        return;
    }

    Captcha.isEnabled = function isEnabled() {
        return typeof grecaptcha !== 'undefined';
    };

    Captcha.prototype.execute = function execute() {
        if (typeof grecaptcha === 'undefined') {
            // Captcha not enabled or grecaptcha -library not loaded
            return false;
        }

        grecaptcha.execute(this.widgetId);

        return true;
    };

    Captcha.prototype.reset = function reset() {
        if (typeof grecaptcha === 'undefined') {
            // Captcha not enabled or grecaptcha -library not loaded
            return false;
        }

        grecaptcha.reset(this.widgetId);

        return true;
    };

    return Captcha;
}();

exports.default = Captcha;

/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


if (typeof Element.prototype.remove !== 'function') {
    Element.prototype.remove = function () {
        this.parentElement.removeChild(this);
    };
}

if (typeof NodeList.prototype.forEach !== 'function') {
    NodeList.prototype.forEach = Array.prototype.forEach;
}

if (typeof Element.prototype.matches !== 'function') {
    Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.mozMatchesSelector || Element.prototype.webkitMatchesSelector || function matches(selector) {
        var element = this;
        var elements = (element.document || element.ownerDocument).querySelectorAll(selector);
        var index = 0;

        while (elements[index] && elements[index] !== element) {
            ++index;
        }

        return Boolean(elements[index]);
    };
}

if (typeof Element.prototype.closest !== 'function') {
    Element.prototype.closest = function closest(selector) {
        var element = this;

        while (element && element.nodeType === 1) {
            if (element.matches(selector)) {
                return element;
            }

            element = element.parentNode;
        }

        return null;
    };
}

/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


// These might not be a good idea. I'm just lazy.
// Hopefully they will not completely break down if some browser implements these functions.

Element.prototype.setAttributes = function (attributes) {
    for (var key in attributes) {
        if (!attributes.hasOwnProperty(key)) {
            return true;
        }

        this.setAttribute(key, attributes[key]);
    }
};

Element.prototype.appendBefore = function (elm) {
    elm.parentNode.insertBefore(this, elm);
};

Element.prototype.appendAfter = function (elm) {
    elm.parentNode.insertBefore(this, elm.nextSibling);
};

Element.prototype.toggle = function () {
    if (window.getComputedStyle(this).display !== 'none') {
        this.hide();
    } else {
        this.show();
    }
};

Element.prototype.hide = function () {
    this.style.display = 'none';
};

Element.prototype.show = function () {
    var style = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'block';

    this.style.display = style;
};

Element.prototype.insertAtCaret = function (before) {
    var after = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';

    if (document.selection) {
        // IE
        var selection = document.selection.createRange();
        selection.text = before + selection.text + after;
        this.focus();
    } else if (this.selectionStart || this.selectionStart === 0) {
        // FF & Chrome
        var selectedText = this.value.substr(this.selectionStart, this.selectionEnd - this.selectionStart);
        var startPos = this.selectionStart;
        var endPos = this.selectionEnd;
        this.value = this.value.substr(0, startPos) + before + selectedText + after + this.value.substr(endPos, this.value.length);

        // Move selection to end of "before" -tag
        this.selectionStart = startPos + before.length;
        this.selectionEnd = startPos + before.length;

        this.focus();
    } else {
        // Nothing selected/not supported, append
        this.value += before + after;
        this.focus();
    }
};

NodeList.prototype.toggle = function () {
    this.forEach(function (elm) {
        elm.toggle();
    });
};

NodeList.prototype.hide = function () {
    this.forEach(function (elm) {
        elm.hide();
    });
};

NodeList.prototype.show = function () {
    this.forEach(function (elm) {
        elm.show();
    });
};

NodeList.prototype.remove = function () {
    this.forEach(function (elm) {
        elm.remove();
    });
};

/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__(5);

__webpack_require__(6);

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

var _YBoard = __webpack_require__(1);

var _YBoard2 = _interopRequireDefault(_YBoard);

var _Toast = __webpack_require__(2);

var _Toast2 = _interopRequireDefault(_Toast);

var _Modal = __webpack_require__(3);

var _Modal2 = _interopRequireDefault(_Modal);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_YQuery2.default.ajaxSetup({
    // AJAX options
    'timeout': 10000,
    'errorFunction': function errorFunction(xhr) {
        var errorMessage = xhr.responseText;
        var errorTitle = messages.errorOccurred;
        if (xhr.responseText.length === 0 && xhr.readyState === 0 && xhr.status === 0) {
            errorMessage = messages.networkError;
        } else {
            if (xhr.responseText === 'timeout') {
                errorMessage = messages.timeoutWarning;
            } else {
                try {
                    var text = JSON.parse(xhr.responseText);
                    errorMessage = text.message;
                    if (typeof text.title !== 'undefined' && text.title !== null && text.title.length !== 0) {
                        errorTitle = text.title;
                    }
                } catch (e) {
                    errorMessage = xhr.responseText;
                }
            }
        }

        if (xhr.status === 410) {
            _Toast2.default.warning(errorMessage);
        } else if (xhr.status === 418) {
            _Toast2.default.error(errorMessage);
        } else {
            _Toast2.default.error(errorMessage, errorTitle);
        }
    },
    'timeoutFunction': function timeoutFunction(xhr) {
        _Toast2.default.error(messages.timeoutWarning);
    }
}, {
    // Headers
    'X-CSRF-Token': typeof csrfToken !== 'undefined' ? csrfToken : null
});

window.YBoard = _YBoard2.default;
window.YQuery = _YQuery2.default;
window.Modal = _Modal2.default;

/***/ }),
/* 8 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Tooltip = function () {
    function Tooltip(e) {
        var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

        _classCallCheck(this, Tooltip);

        this.options = Object.assign({
            'openDelay': 100,
            'offset': 10,
            'content': '',
            'onOpen': null,
            'onClose': null,
            'closeEvent': 'mouseout',
            'position': 'bottom'
        }, options);

        // Placeholders for tip position
        this.x = 0;
        this.y = 0;
        this.spaceAvailable = {
            'top': 0,
            'right': 0,
            'bottom': 0,
            'left': 0
        };

        // Other
        this.overflows = false;
        this.event = e;
        this.id = 0;

        this.open();
    }

    Tooltip.prototype.open = function open() {
        var that = this;

        this.elm = document.createElement('div');
        this.elm.classList.add('tooltip');

        var lastTip = document.querySelector('.tooltip:last-of-type');
        if (lastTip !== null) {
            this.id = parseInt(document.querySelector('.tooltip:last-of-type').dataset.id) + 1;
        }
        this.elm.dataset.id = this.id;

        this.setContent(this.options.content, false);

        var closeEventFn = function closeEventFn() {
            that.event.target.removeEventListener(that.options.closeEvent, closeEventFn);
            that.close(that);
        };

        this.event.target.addEventListener(this.options.closeEvent, closeEventFn);
        this.event.target.tooltip = this;

        if (this.options.openDelay !== 0) {
            setTimeout(function () {
                if (that.elm === null) {
                    return;
                }
                that.appendTip();
            }, this.options.openDelay);
        } else {
            this.appendTip();
        }
    };

    Tooltip.prototype.appendTip = function appendTip() {
        document.body.appendChild(this.elm);

        if (typeof this.options.onOpen === 'function') {
            this.options.onOpen(this);
        }

        this.position();
    };

    Tooltip.prototype.setContent = function setContent(content) {
        var position = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;

        if (this.elm === null) {
            return;
        }

        this.elm.innerHTML = '<div class="tooltip-content">' + content + '</div>';

        if (position) {
            this.position();
        }
    };

    Tooltip.prototype.getContent = function getContent() {
        if (this.elm === null) {
            return;
        }

        return this.elm.querySelector('.tooltip-content').innerHTML;
    };

    Tooltip.prototype.close = function close() {
        if (typeof this.options.onClose === 'function') {
            this.options.onClose(this);
        }

        this.elm = null;

        var tip = document.querySelector('.tooltip[data-id="' + this.id + '"]');

        if (tip !== null) {
            tip.remove();
        }
    };

    Tooltip.prototype.position = function position() {
        if (this.elm === null) {
            return;
        }

        var style = window.getComputedStyle(this.elm);
        this.targetRect = this.getRect(this.event.target);
        this.tipRect = this.getRect(this.elm, style);

        this.spaceAvailable = {
            'top': this.targetRect.top - this.options.offset - parseFloat(style.marginBottom) - parseFloat(style.marginTop),
            'right': window.innerWidth - this.targetRect.right - this.options.offset - parseFloat(style.marginLeft) - parseFloat(style.marginRight),
            'bottom': window.innerHeight - this.targetRect.bottom - this.options.offset - parseFloat(style.marginBottom) - parseFloat(style.marginTop),
            'left': this.targetRect.left - this.options.offset - parseFloat(style.marginLeft) - parseFloat(style.marginRight)
        };

        this.calculatePosition(this.options.position);
        this.elm.classList.add(this.options.position);

        this.setPosition();
    };

    Tooltip.prototype.calculatePosition = function calculatePosition(position) {
        this.options.position = position;

        // Calculate X
        switch (position) {
            case 'top':
            case 'bottom':
                this.x = this.targetRect.right - this.targetRect.width / 2 - this.tipRect.width / 2;

                if (this.x + this.tipRect.width > window.innerWidth) {
                    this.x = window.innerWidth - this.tipRect.width;
                }

                if (this.x < 0) {
                    this.x = 0;
                }

                break;
            case 'right':
                this.x = this.targetRect.right + this.options.offset;
                if (this.tipRect.width + this.options.offset > this.spaceAvailable.right) {
                    if (this.overflows || this.spaceAvailable.left < this.spaceAvailable.right) {
                        // Fits better to right than to left
                        this.elm.style.maxWidth = this.spaceAvailable.right + 'px';
                        this.tipRect = this.getRect(this.elm);
                    } else {
                        // Overflows, position on left
                        return this.recalculatePosition('left');
                    }
                }

                break;
            case 'left':
                this.x = this.targetRect.left - this.tipRect.width - this.options.offset;
                if (this.x < 0) {
                    if (this.overflows || this.spaceAvailable.right < this.spaceAvailable.left) {
                        // Fits better to left than to right
                        this.elm.style.maxWidth = this.spaceAvailable.left + 'px';
                        this.tipRect = this.getRect(this.elm);
                    } else {
                        // Overflows, position on right
                        return this.recalculatePosition('right');
                    }
                }
                break;
        }

        // Calculate Y
        switch (position) {
            case 'top':
                this.y = this.targetRect.top - this.tipRect.height - this.options.offset;
                if (this.y < 0) {
                    // Tip is larger than available space
                    if (this.overflows || this.spaceAvailable.bottom < this.spaceAvailable.top) {
                        // Fits better to top than to bottom
                        this.y = 0;
                        this.elm.style.maxHeight = this.spaceAvailable.top + 'px';
                        this.tipRect = this.getRect(this.elm);
                    } else {
                        // Overflows, position on bottom
                        return this.recalculatePosition('bottom');
                    }
                }
                break;
            case 'bottom':
                this.y = this.targetRect.bottom + this.options.offset;
                if (this.tipRect.height + this.options.offset > this.spaceAvailable.bottom) {
                    // Tip is larger than available space
                    if (this.overflows || this.spaceAvailable.top < this.spaceAvailable.bottom) {
                        // Fits better to bottom than to top
                        this.elm.style.maxHeight = this.spaceAvailable.bottom + 'px';
                        this.tipRect = this.getRect(this.elm);
                    } else {
                        // Overflows, position on top
                        return this.recalculatePosition('top');
                    }
                }
                break;
            case 'right':
            case 'left':
                this.y = this.targetRect.bottom - this.targetRect.height / 2 - this.tipRect.height / 2;
                if (this.y < 0) {
                    this.y = 0;
                }
                break;
        }
    };

    Tooltip.prototype.getRect = function getRect(elm) {
        var style = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

        var rect = elm.getBoundingClientRect();
        if (style === null) {
            style = window.getComputedStyle(elm);
        }

        return {
            'top': rect.top - parseFloat(style.marginTop) - parseFloat(style.borderTopWidth),
            'right': rect.right + parseFloat(style.marginRight) + parseFloat(style.borderRightWidth),
            'bottom': rect.bottom + parseFloat(style.marginBottom) + parseFloat(style.borderBottomWidth),
            'left': rect.left - parseFloat(style.marginLeft) - parseFloat(style.borderLeftWidth),
            'width': rect.width + parseFloat(style.marginLeft) + parseFloat(style.borderLeftWidth) + parseFloat(style.marginRight) + parseFloat(style.borderRightWidth),
            'height': rect.height + parseFloat(style.marginTop) + parseFloat(style.borderTopWidth) + parseFloat(style.marginBottom) + parseFloat(style.borderBottomWidth)
        };
    };

    Tooltip.prototype.recalculatePosition = function recalculatePosition(position) {
        this.overflows = true;
        this.calculatePosition(position);
    };

    Tooltip.prototype.setPosition = function setPosition() {
        this.elm.style.left = window.scrollX + this.x + 'px';
        this.elm.style.top = window.scrollY + this.y + 'px';
    };

    return Tooltip;
}();

exports.default = Tooltip;

/***/ }),
/* 9 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Catalog = function () {
    function Catalog() {
        _classCallCheck(this, Catalog);

        var searchInput = document.getElementById('search-catalog');
        if (searchInput) {
            searchInput.addEventListener('keyup', this.search);
        }
    }

    Catalog.prototype.search = function search(e) {
        var elm = e.target;
        var word = elm.value;
        var threads = document.querySelectorAll('.thread');

        if (word.length === 0) {
            threads.forEach(function (elm) {
                elm.style.display = '';
            });
        } else {
            threads.hide();
            threads.forEach(function (elm) {
                if (elm.querySelector('h3').innerHTML.toLowerCase().indexOf(word.toLowerCase()) !== -1) {
                    elm.show('flex');
                    return true;
                }
                if (elm.querySelector('.message').innerHTML.toLowerCase().indexOf(word.toLowerCase()) !== -1) {
                    elm.show('flex');
                    return true;
                }
            });
        }
    };

    return Catalog;
}();

exports.default = Catalog;

/***/ }),
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _Modal = __webpack_require__(3);

var _Modal2 = _interopRequireDefault(_Modal);

var _YBoard = __webpack_require__(1);

var _YBoard2 = _interopRequireDefault(_YBoard);

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Notifications = function () {
    function Notifications() {
        _classCallCheck(this, Notifications);

        var that = this;
        this.modal = null;

        document.querySelectorAll('.e-open-notifications').forEach(function (elm) {
            elm.addEventListener('click', function () {
                that.open();
            });
        });
    }

    Notifications.prototype.open = function open() {
        var that = this;
        var postXhr = null;

        this.modal = new _Modal2.default({
            'title': messages.notifications,
            'content': _YBoard2.default.spinnerHtml(),
            'onOpen': function onOpen(modal) {
                modal.elm.style.willChange = 'contents';
                postXhr = _YQuery2.default.post('/api/user/notification/getall', {}, {
                    'errorFunction': null
                }).onLoad(function (xhr) {
                    if (modal.elm === null) {
                        return;
                    }
                    modal.setContent(xhr.responseText);
                    modal.elm.style.willChange = '';
                    that.bindEvents(modal.elm);
                    that.updateUnreadCount(that.getUnreadCount(modal.elm));
                }).onError(function (xhr) {
                    if (xhr.responseText.length !== 0) {
                        var json = JSON.parse(xhr.responseText);
                        modal.setContent(json.message);
                    } else {
                        modal.setContent(messages.errorOccurred);
                    }
                });
                postXhr = postXhr.getXhrObject();
            },
            'onClose': function onClose() {
                if (postXhr !== null && postXhr.readyState !== 4) {
                    postXhr.abort();
                }
            }
        });
    };

    Notifications.prototype.bindEvents = function bindEvents(elm) {
        var that = this;

        // Clicking a link to go to the post
        elm.querySelectorAll('a').forEach(function (elm) {
            elm.addEventListener('click', function (e) {

                // Close modal, just in case we are just highlighting something
                if (that.modal !== null) {
                    if (typeof e.target.tooltip !== 'undefined') {
                        e.target.tooltip.close();
                    }
                    that.modal.close();
                }

                // Mark as read
                if (e.target.closest('.notification').classList.contains('not-read')) {
                    var beaconUrl = '/api/user/notification/markread';
                    var data = new FormData();
                    data.append('id', e.target.closest('.notification').dataset.id);
                    data.append('csrfToken', csrfToken);
                    if ('sendBeacon' in navigator) {
                        // Way faster
                        navigator.sendBeacon(beaconUrl, data);
                    } else {
                        // Fallback for IE ... and SAFARI! Sheesh...
                        e.preventDefault();
                        _YQuery2.default.post(beaconUrl, data).onLoad(function () {
                            window.location = e.target.getAttribute('href');
                        });
                    }
                }
            });
        });

        // Marking all notifications as read
        elm.querySelectorAll('.e-mark-all-read').forEach(function (elm) {
            elm.addEventListener('click', function (e) {
                that.markAllRead(e, that);
            });
        });

        // Marking a single notification as read
        elm.querySelectorAll('.e-mark-read').forEach(function (elm) {
            elm.addEventListener('click', function (e) {
                that.markRead(e, that);
            });
        });

        // Everything else
        _YBoard2.default.initElement(elm);
    };

    Notifications.prototype.markRead = function markRead(e, that) {
        var notification = e.target.closest('.notification');
        notification.classList.remove('not-read');
        notification.classList.add('is-read');

        _YQuery2.default.post('/api/user/notification/markread', { 'id': notification.dataset.id }).onError(function () {
            notification.classList.add('not-read');
            notification.classList.remove('is-read');
        });

        that.updateUnreadCount(that.getUnreadCount(e.target.closest('.notifications-list')));
    };

    Notifications.prototype.markAllRead = function markAllRead(e, that) {
        e.target.closest('.modal').querySelectorAll('.notification.not-read').forEach(function (elm) {
            elm.classList.remove('not-read');
            elm.classList.add('is-read');
        });

        _YQuery2.default.post('/api/user/notification/markallread');
        that.updateUnreadCount(0);
        that.modal.close();
    };

    Notifications.prototype.getUnreadCount = function getUnreadCount(elm) {
        return elm.querySelectorAll('.notification.not-read').length;
    };

    Notifications.prototype.updateUnreadCount = function updateUnreadCount(count) {
        count = parseInt(count);
        if (count >= 100) {
            count = ':D';
        }

        document.querySelectorAll('.unread-notifications').forEach(function (elm) {
            elm.innerHTML = count;

            if (count === 0) {
                elm.classList.add('none');
            } else {
                elm.classList.remove('none');
            }
        });
    };

    return Notifications;
}();

exports.default = Notifications;

/***/ }),
/* 11 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

var _YBoard = __webpack_require__(1);

var _YBoard2 = _interopRequireDefault(_YBoard);

var _Toast = __webpack_require__(2);

var _Toast2 = _interopRequireDefault(_Toast);

var _File = __webpack_require__(13);

var _File2 = _interopRequireDefault(_File);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Post = function () {
    function Post() {
        _classCallCheck(this, Post);

        this.File = new _File2.default(this);
    }

    Post.prototype.bindEvents = function bindEvents(elm) {
        var that = this;
        this.File.bindEvents(elm);

        elm.querySelectorAll('.e-post-delete').forEach(function (elm) {
            elm.addEventListener('click', that.delete);
        });

        elm.querySelectorAll('.ref').forEach(function (elm) {
            elm.addEventListener('click', that.refClick);
        });
    };

    Post.prototype.refClick = function refClick(e) {
        var referred = e.currentTarget.dataset.id;
        if (typeof referred === 'undefined') {
            return true;
        }

        if (document.getElementById('post-' + referred) !== null) {
            e.preventDefault();
            document.location.hash = '#post-' + referred;
        }
    };

    Post.prototype.getElm = function getElm(id) {
        return document.getElementById('post-' + id);
    };

    Post.prototype.delete = function _delete(e) {
        if (!confirm(messages.confirmDeletePost)) {
            return false;
        }

        var post = e.target.closest('.post, .thread');
        var id = post.dataset.id;
        _YQuery2.default.post('/api/post/delete', { 'postId': id }).onLoad(function () {
            post.remove();
            if (_YBoard2.default.Thread.getElm(id) !== null) {
                if (document.body.classList.contains('thread-page')) {
                    // We're in the thread we just deleted
                    _YBoard2.default.returnToBoard();
                } else {
                    // The deleted post is a thread and not the opened thread
                    _YBoard2.default.Thread.getElm(id).remove();
                }
            }
            _Toast2.default.success(messages.postDeleted);
        });
    };

    return Post;
}();

exports.default = Post;

/***/ }),
/* 12 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

var _YBoard = __webpack_require__(1);

var _YBoard2 = _interopRequireDefault(_YBoard);

var _Toast = __webpack_require__(2);

var _Toast2 = _interopRequireDefault(_Toast);

var _Captcha = __webpack_require__(4);

var _Captcha2 = _interopRequireDefault(_Captcha);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var PostForm = function () {
    function PostForm() {
        _classCallCheck(this, PostForm);

        var that = this;
        this.elm = document.getElementById('post-form');
        if (this.elm === null) {
            return;
        }

        this.captcha = null;
        this.locationParent = this.elm.parentNode;
        this.location = this.elm.nextElementSibling;
        this.msgElm = this.elm.querySelector('#post-message');
        this.fileUploadInProgress = false;
        this.fileUploadXhr = null;
        this.submitAfterFileUpload = false;
        this.submitInProgress = false;
        this.skipUnloadWarning = false;
        this.origDestName = false;
        this.origDestValue = false;

        // BBCode buttons
        this.elm.querySelectorAll('.bb-code').forEach(function (elm) {
            elm.addEventListener('click', function (e) {
                that.insertBbCode(e.target.dataset.code);
            });
        });
        this.elm.querySelector('.e-postform-color-bar').addEventListener('click', function () {
            that.toggleBbColorBar();
        });

        // Create thread
        document.querySelectorAll('.create-thread').forEach(function (elm) {
            elm.addEventListener('click', function () {
                that.show();
            });
        });

        // Reply to a thread
        document.querySelectorAll('.e-thread-reply').forEach(function (elm) {
            elm.addEventListener('click', function (e) {
                e.preventDefault();

                var threadId = null;
                var thread = e.target.closest('.thread');
                if (thread !== null) {
                    threadId = thread.dataset.id;
                }

                that.threadReply(threadId);
            });
        });

        // Cancel post
        this.elm.querySelector('#reset-button').addEventListener('click', function (e) {
            if (that.msgElm.value.length === 0 || confirm(messages.confirmPostCancel)) {
                e.preventDefault();
                that.reset(true);
            }
        });

        // Upload file after change
        this.elm.querySelector('#post-files').addEventListener('change', function () {
            that.uploadFile();
        });

        // Remove file -button
        this.elm.querySelector('#remove-file').addEventListener('click', function () {
            that.deleteUploadedFile();
            that.removeFile();
        });

        // Toggle post options
        this.elm.querySelector('.toggle-options').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('post-options').toggle();
        });

        // Submit a post
        this.elm.addEventListener('submit', function (e) {
            that.submit(e);
        });

        this.elm.querySelectorAll('input, select, textarea').forEach(function (elm) {
            elm.addEventListener('focus', function (e) {
                that.renderCaptcha();
            });
        });

        // Confirm page leave if there's text in the form
        window.addEventListener('beforeunload', function (e) {
            if (!that.skipUnloadWarning && that.msgElm !== null && that.msgElm.value.length !== 0) {
                e.returnValue = messages.confirmPageLeave;
            }
        });
    }

    PostForm.prototype.bindPostEvents = function bindPostEvents(elm) {
        var that = this;

        // Reply to a post
        elm.querySelectorAll('.e-post-reply').forEach(function (elm) {
            elm.addEventListener('click', function (e) {
                e.preventDefault();
                that.postReply(e.target.closest('.post').dataset.id);
                that.msgElm.focus();
            });
        });
    };

    PostForm.prototype.show = function show(isReply) {
        if (!isReply) {
            // Reset if we click the "Create thread" -button
            this.reset();
        }

        this.renderCaptcha();

        this.elm.classList.add('visible');
        if (this.msgElm.offsetParent !== null) {
            this.msgElm.focus();
        }
    };

    PostForm.prototype.renderCaptcha = function renderCaptcha() {
        var that = this;
        if (this.captcha !== null || !_Captcha2.default.isEnabled()) {
            return;
        }

        this.captcha = new _Captcha2.default(this.elm.querySelector('.captcha'), {
            'size': 'invisible',
            'callback': function callback(response) {
                that.submit(null, response);
            }
        });
    };

    PostForm.prototype.hide = function hide() {
        this.elm.classList.remove('visible');
    };

    PostForm.prototype.reset = function reset() {
        var resetForm = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

        if (resetForm) {
            this.elm.reset();
        }

        if (this.location !== null) {
            this.locationParent.insertBefore(this.elm, this.location);
        } else {
            this.locationParent.appendChild(this.elm);
        }

        this.removeFile();
        this.resetDestination();
        this.hide();
    };

    PostForm.prototype.focus = function focus() {
        this.msgElm.focus();
    };

    PostForm.prototype.setDestination = function setDestination(isReply, destination) {
        this.saveDestination();
        var name = 'board';

        if (isReply) {
            name = 'thread';
        }

        var postDestination = this.elm.querySelector('#post-destination');
        postDestination.setAttribute('name', name);
        postDestination.value = destination;
    };

    PostForm.prototype.saveDestination = function saveDestination() {
        var destElm = this.elm.querySelector('#post-destination');
        var boardSelector = this.elm.querySelector('#label-board');

        // Hide board selector
        if (boardSelector !== null) {
            boardSelector.hide();
            boardSelector.querySelector('select').required = false;
            return true;
        }

        if (this.origDestName) {
            return true;
        }

        this.origDestName = destElm.getAttribute('name');
        this.origDestValue = destElm.value;

        return true;
    };

    PostForm.prototype.resetDestination = function resetDestination() {
        var destElm = this.elm.querySelector('#post-destination');
        var boardSelector = this.elm.querySelector('#label-board');

        // Restore board selector
        if (boardSelector !== null) {
            boardSelector.show('flex');
            boardSelector.querySelector('select').required = true;
        }

        if (!this.origDestName) {
            return true;
        }

        destElm.setAttribute('name', this.origDestName);
        destElm.value = this.origDestValue;

        this.origDestName = false;
        this.origDestValue = false;

        return true;
    };

    PostForm.prototype.insertBbCode = function insertBbCode(code) {
        this.msgElm.insertAtCaret('[' + code + ']', '[/' + code + ']');
    };

    PostForm.prototype.toggleBbColorBar = function toggleBbColorBar() {
        this.elm.querySelector('#color-buttons').toggle();
        this.msgElm.focus();
    };

    PostForm.prototype.uploadFile = function uploadFile() {
        this.elm.querySelector('#remove-file').show();

        // Abort any ongoing uploads
        this.deleteUploadedFile();
        this.removeFile(true);

        var fileInput = this.elm.querySelector('#post-files');
        var fileNameElm = this.elm.querySelector('#file-name');

        fileNameElm.value = '';
        this.submitAfterFileUpload = false;

        this.updateFileProgressBar(1);

        // Calculate upload size and check it does not exceed the set maximum
        var maxSize = fileInput.dataset.maxsize;
        var fileList = fileInput.files;
        var fileSizeSum = 0;
        for (var i = 0, file; file = fileList[i]; i++) {
            fileSizeSum += file.size;
        }

        if (fileSizeSum > maxSize) {
            _Toast2.default.warning(messages.maxSizeExceeded);
            this.updateFileProgressBar(0);
            return false;
        }

        var fd = new FormData();
        Array.from(fileList).forEach(function (file) {
            fd.append('files[]', file);
        });

        this.fileUploadInProgress = true;

        var fileName = fileInput.value.split('\\').pop().split('.');
        fileName.pop();
        fileNameElm.value = fileName.join('.');

        var that = this;
        var fileUpload = _YQuery2.default.post('/api/file/create', fd, {
            'timeout': 0,
            'contentType': null,
            'xhr': function xhr(_xhr) {
                if (!_xhr.upload) {
                    return _xhr;
                }

                _xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var percent = Math.round(e.loaded / e.total * 100);
                        if (percent < 0) {
                            percent = 0;
                        } else {
                            if (percent > 99) {
                                percent = 99;
                            }
                        }
                        that.updateFileProgressBar(percent);
                    }
                });

                return _xhr;
            }
        }).onLoad(function (xhr) {
            that.fileUploadInProgress = false;
            that.updateFileProgressBar(100);
            var data = JSON.parse(xhr.responseText);
            if (data.message.length !== 0) {
                that.elm.querySelector('#file-id').value = data.message[0];

                if (that.submitAfterFileUpload) {
                    that.submit();
                }
            } else {
                _Toast2.default.error(messages.errorOccurred);
                that.removeFile();
                that.updateFileProgressBar(0);
            }
        }).onError(function () {
            that.removeFile();
            that.updateFileProgressBar(0);
        });

        this.fileUploadXhr = fileUpload.getXhrObject();
    };

    PostForm.prototype.removeFile = function removeFile() {
        var refresh = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

        if (this.fileUploadXhr !== null) {
            this.fileUploadXhr.abort();
            this.fileUploadXhr = null;
        }

        if (!refresh) {
            this.elm.querySelector('#remove-file').hide();
            this.elm.querySelector('#post-files').value = '';
        }

        this.elm.querySelector('#file-id').value = '';
        this.elm.querySelector('#file-name').value = '';
        this.updateFileProgressBar(0);
        this.elm.querySelector('input[type="submit"].button').disabled = false;
        this.fileUploadInProgress = false;
        this.submitAfterFileUpload = false;
    };

    PostForm.prototype.deleteUploadedFile = function deleteUploadedFile() {
        var fileIdElm = this.elm.querySelector('#file-id');
        if (fileIdElm.value !== '') {
            _YQuery2.default.post('/api/file/delete', {
                'fileId': fileIdElm.value
            });
        }
    };

    PostForm.prototype.updateFileProgressBar = function updateFileProgressBar(progress) {
        if (progress < 0) {
            progress = 0;
        } else {
            if (progress > 100) {
                progress = 100;
            }
        }

        var elm = this.elm.querySelector('.file-progress');
        var bar = elm.querySelector('div');

        if (progress === 0) {
            bar.style.width = 0;
            elm.classList.remove('in-progress');
        } else {
            bar.style.width = progress + '%';
            elm.classList.add('in-progress');
        }
    };

    PostForm.prototype.threadReply = function threadReply(threadId) {
        if (threadId !== null) {
            _YBoard2.default.Thread.getElm(threadId).appendChild(this.elm);
            this.show(true);
            this.setDestination(true, threadId);
        }

        this.msgElm.focus();
    };

    PostForm.prototype.postReply = function postReply(postId) {
        var selectedText = _YBoard2.default.getSelectionText();

        _YBoard2.default.Post.getElm(postId).appendChild(this.elm);
        this.show(true);

        this.setDestination(true, this.elm.closest('.thread').dataset.id);

        this.msgElm.focus();
        var append = '';
        if (this.msgElm.value.substr(-1) !== '\n' && this.msgElm.value.length !== 0) {
            if (this.msgElm.value.substr(-1) !== '\n') {
                append += '\n\n';
            } else {
                append += '\n';
            }
        }
        append += '>>' + postId + '\n';

        // If any text on the page was selected, add it to post form with quotes
        if (selectedText !== '') {
            append += '>' + selectedText.replace(/(\r\n|\n|\r)/g, '$1>') + '\n';
        }

        this.msgElm.insertAtCaret(append);
    };

    PostForm.prototype.submit = function submit() {
        var e = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
        var captchaResponse = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

        var that = this;
        if ((typeof e === 'undefined' ? 'undefined' : _typeof(e)) === 'object' && e !== null) {
            e.preventDefault();
        }

        // Invoke the reCAPTCHA if the response is null (= not completed)
        if (this.captcha !== null && captchaResponse === null) {
            console.log(2);
            this.captcha.execute();

            return;
        }

        var submitButton = this.elm.querySelector('input[type="submit"].button');

        // File upload in progress -> wait until done
        if (this.fileUploadInProgress) {
            _Toast2.default.info(messages.waitingForFileUpload);
            submitButton.disabled = true;
            this.submitAfterFileUpload = true;
            return false;
        }

        // Prevent duplicate submissions by double clicking etc.
        if (this.submitInProgress) {
            return false;
        }
        this.submitInProgress = true;

        this.elm.querySelector('#post-files').value = '';

        var fd = new FormData(this.elm);
        fd.append('captchaResponse', captchaResponse);

        _YQuery2.default.post('/api/post/create', fd, {
            'contentType': null
        }).onLoad(function (xhr) {
            var dest = document.getElementById('post-destination');
            var thread = void 0;
            if (dest.getAttribute('name') !== 'thread') {
                thread = null;
            } else {
                thread = dest.value;
            }

            if (thread !== null) {
                _YBoard2.default.Thread.AutoUpdate.runOnce(thread);
                // Reset post form
                that.reset(true);
            } else {
                if (xhr.responseText.length === 0) {
                    that.skipUnloadWarning = true;
                    _YBoard2.default.pageReload();
                } else {
                    var data = JSON.parse(xhr.responseText);
                    if (typeof data.message === 'undefined') {
                        _Toast2.default.error(messages.errorOccurred);
                    } else {
                        that.skipUnloadWarning = true;
                        window.location = '/' + fd.get('board') + '/' + data.message;
                    }
                }
            }
        }).onEnd(function () {
            submitButton.disabled = false;
            that.submitInProgress = false;

            if (that.captcha !== null) {
                that.captcha.reset();
            }
        });
    };

    return PostForm;
}();

exports.default = PostForm;

/***/ }),
/* 13 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

var _YBoard = __webpack_require__(1);

var _YBoard2 = _interopRequireDefault(_YBoard);

var _Toast = __webpack_require__(2);

var _Toast2 = _interopRequireDefault(_Toast);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var File = function () {
    function File(Post) {
        _classCallCheck(this, File);

        this.Post = Post;
    }

    File.prototype.bindEvents = function bindEvents(parent) {
        var that = this;

        parent.querySelectorAll('.thumbnail .image').forEach(function (elm) {
            elm.addEventListener('click', function (e) {
                that.expand(e, that);
            });
        });

        parent.querySelectorAll('.thumbnail .media').forEach(function (elm) {
            elm.addEventListener('click', function (e) {
                that.playMedia(e, that);
            });
        });

        parent.querySelectorAll('.e-stop-media').forEach(function (elm) {
            elm.addEventListener('click', that.stopAllMedia);
        });
    };

    File.prototype.delete = function _delete(id) {
        if (!confirm(messages.confirmDeleteFile)) {
            return false;
        }

        _YQuery2.default.post('/api/post/deletefile', {
            'post_id': id,
            'loadFunction': function loadFunction() {
                this.getElm(id).find('figure').remove();
                _Toast2.default.success(messages.fileDeleted);
            }
        });
    };

    File.prototype.expand = function expand(e, that) {
        function changeSrc(img, src) {
            var eolFn = expandOnLoad;
            function expandOnLoad(e) {
                e.target.removeEventListener('load', eolFn);
                delete e.target.dataset.expanding;
                clearTimeout(e.target.loading);
                var overlay = e.target.parentNode.querySelector('div.overlay');
                if (overlay !== null) {
                    requestAnimationFrame(function () {
                        overlay.remove();
                    });
                }
            }

            img.dataset.expanding = true;
            img.loading = setTimeout(function () {
                var overlay = document.createElement('div');
                overlay.classList.add('overlay', 'center');
                overlay.innerHTML = _YBoard2.default.spinnerHtml();
                img.parentNode.appendChild(overlay);
            }, 200);

            img.addEventListener('load', eolFn);
            img.setAttribute('src', src);
        }

        e.preventDefault();
        if (typeof e.target.dataset.expanded === 'undefined') {
            // Expand
            e.target.dataset.expanded = e.target.getAttribute('src');
            requestAnimationFrame(function () {
                changeSrc(e.target, e.target.parentNode.getAttribute('href'));
                e.target.closest('.post-file').classList.remove('thumbnail');
            });
        } else {
            // Restore thumbnail
            requestAnimationFrame(function () {
                changeSrc(e.target, e.target.dataset.expanded);
                e.target.closest('.post-file').classList.add('thumbnail');
                delete e.target.dataset.expanded;

                // Scroll to top of image
                var elmTop = e.target.getBoundingClientRect().top + window.scrollY;
                if (elmTop < window.scrollY) {
                    window.scrollTo(0, elmTop);
                }
            });
        }
    };

    File.prototype.playMedia = function playMedia(e, that) {
        e.preventDefault();

        that.stopAllMedia();

        var fileId = e.target.closest('figure').dataset.id;

        if (typeof e.target.dataset.loading !== 'undefined') {
            return false;
        }

        e.target.dataset.loading = true;

        var loading = setTimeout(function () {
            var overlay = document.createElement('div');
            overlay.classList.add('overlay', 'bottom', 'left');
            overlay.innerHTML = _YBoard2.default.spinnerHtml();

            requestAnimationFrame(function () {
                e.target.appendChild(overlay);
            });
        }, 200);

        _YQuery2.default.post('/api/file/getmediaplayer', { 'fileId': fileId }).onLoad(function (xhr) {
            var figure = e.target.closest('.post-file');
            var message = e.target.closest('.message');

            var data = document.createElement('div');
            data.innerHTML = xhr.responseText;

            // Bind events etc.
            _YBoard2.default.initElement(data);

            requestAnimationFrame(function () {
                figure.classList.remove('thumbnail');
                figure.classList.add('media-player-container');
                figure.insertBefore(data, figure.firstElementChild);

                // Video volume save/restore
                var video = figure.querySelector('video');
                if (video !== null) {
                    video.addEventListener('volumechange', function (e) {
                        localStorage.setItem('videoVolume', e.target.volume);
                    });

                    var volume = localStorage.getItem('videoVolume');
                    if (volume !== null) {
                        video.volume = volume;
                    }
                }
            });
        }).onEnd(function () {
            clearTimeout(loading);
            e.target.querySelectorAll('div.overlay').forEach(function (elm) {
                requestAnimationFrame(function () {
                    elm.remove();
                });
            });
            delete e.target.dataset.loading;
        });
    };

    File.prototype.stopAllMedia = function stopAllMedia() {
        document.querySelectorAll('.media-player-container').forEach(function (elm) {
            var video = elm.querySelector('video');
            video.pause();
            video.remove();

            requestAnimationFrame(function () {
                elm.classList.remove('media-player-container');
                elm.classList.add('thumbnail');
            });
        });
    };

    return File;
}();

exports.default = File;

/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Theme = function () {
    function Theme() {
        _classCallCheck(this, Theme);

        var that = this;

        // Switch theme
        document.querySelector('.e-switch-theme').addEventListener('click', function () {
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
        document.getElementById('e-sidebar-hide').addEventListener('click', function () {
            that.toggleSidebar();
        });
    }

    Theme.prototype.toggleSidebar = function toggleSidebar() {
        document.body.classList.toggle('no-sidebar');
        var hide = document.body.classList.contains('no-sidebar');
        if (hide) {
            document.body.classList.remove('sidebar-visible');
        }

        _YQuery2.default.post('/api/user/preferences/set', {
            'hideSidebar': hide
        });
    };

    Theme.prototype.switchVariation = function switchVariation() {
        var css = document.querySelectorAll('head .css');
        css = css[css.length - 1];

        var light = css.dataset.light;
        var dark = css.dataset.dark;
        var currentIsDark = css.dataset.darktheme === 'true';

        var newVariation = void 0;
        var darkTheme = void 0;
        if (currentIsDark) {
            newVariation = light;
            darkTheme = false;
        } else {
            newVariation = dark;
            darkTheme = true;
        }

        var newCss = css.cloneNode();

        newCss.setAttributes({
            'href': newVariation,
            'data-darkTheme': darkTheme
        });
        newCss.appendAfter(css);

        var timeout = setTimeout(function () {
            css.remove();
        }, 5000);

        _YQuery2.default.post('/api/user/preferences/set', {
            'darkTheme': darkTheme
        });
    };

    return Theme;
}();

exports.default = Theme;

/***/ }),
/* 15 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

var _YBoard = __webpack_require__(1);

var _YBoard2 = _interopRequireDefault(_YBoard);

var _AutoUpdate = __webpack_require__(16);

var _AutoUpdate2 = _interopRequireDefault(_AutoUpdate);

var _Hide = __webpack_require__(18);

var _Hide2 = _interopRequireDefault(_Hide);

var _Follow = __webpack_require__(17);

var _Follow2 = _interopRequireDefault(_Follow);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Thread = function () {
    function Thread() {
        _classCallCheck(this, Thread);

        var that = this;
        this.AutoUpdate = new _AutoUpdate2.default();
        this.Hide = new _Hide2.default();
        this.Follow = new _Follow2.default();

        document.querySelectorAll('.replies-buttons').forEach(function (elm) {
            elm.querySelector('.e-more-replies').addEventListener('click', that.expand);
            elm.querySelector('.e-less-replies').addEventListener('click', that.shrink);
        });
    }

    Thread.prototype.getElm = function getElm(id) {
        return document.getElementById('thread-' + id);
    };

    Thread.prototype.toggleLock = function toggleLock(id) {
        if (this.getElm(id).find('h3 a .icon-lock').length == 0) {
            $.post('/scripts/threads/lock', { 'threadId': id }).done(function () {
                YB.thread.getElm(id).find('h3 a').prepend('<span class="icon-lock icon"></span>');
                toastr.success(messages.threadLocked);
            });
        } else {
            $.post('/scripts/threads/unlock', { 'threadId': id }).done(function () {
                YB.thread.getElm(id).find('h3 a .icon-lock').remove();
                toastr.success(messages.threadUnlocked);
            });
        }
    };

    Thread.prototype.toggleSticky = function toggleSticky(id) {
        if (this.getElm(id).find('h3 a .icon-lock').length == 0) {
            $.post('/scripts/threads/stick', { 'threadId': id }).done(function () {
                YB.thread.getElm(id).find('h3 a').prepend('<span class="icon-pushpin icon"></span>');
                toastr.success(messages.threadStickied);
            });
        } else {
            $.post('/scripts/threads/unstick', { 'threadId': id }).done(function () {
                YB.thread.getElm(id).find('h3 a .icon-pushpin').remove();
                toastr.success(messages.threadUnstickied);
            });
        }
    };

    Thread.prototype.shrink = function shrink(e) {
        var thread = e.target.closest('.thread');
        thread.querySelector('.more-replies-container').innerHTML = '';
        thread.querySelector('.e-more-replies').show();
        thread.classList.remove('expanded');
    };

    Thread.prototype.expand = function expand(e) {
        // Thread inline expansion
        var thread = e.target.closest('.thread');
        var threadId = thread.dataset.id;
        var fromId = thread.querySelector('.reply');
        if (fromId !== null) {
            fromId = fromId.dataset.id;
        }
        var loadCount = 100;

        _YQuery2.default.post('/api/thread/getreplies', {
            'threadId': threadId,
            'fromId': fromId,
            'count': loadCount
        }).onLoad(function (xhr) {
            var data = document.createElement('div');
            data.innerHTML = xhr.responseText;

            var loadedCount = data.querySelectorAll('.post').length;
            if (loadedCount < loadCount) {
                thread.querySelector('.e-more-replies').hide();
            }

            var expandContainer = thread.querySelector('.more-replies-container');
            var firstVisibleReply = expandContainer.querySelector('div');
            if (firstVisibleReply === null) {
                expandContainer.appendChild(data);
            } else {
                expandContainer.insertBefore(data, firstVisibleReply);
            }

            _YBoard2.default.initElement(data);

            thread.classList.add('expanded');
        });
    };

    return Thread;
}();

exports.default = Thread;

/***/ }),
/* 16 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _YBoard = __webpack_require__(1);

var _YBoard2 = _interopRequireDefault(_YBoard);

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

var _Toast = __webpack_require__(2);

var _Toast2 = _interopRequireDefault(_Toast);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var AutoUpdate = function () {
    function AutoUpdate() {
        _classCallCheck(this, AutoUpdate);

        var that = this;
        this.threadId = false;
        this.nextLoadDelay = 2000;
        this.newReplies = 0;
        this.lastUpdateNewReplies = 0;
        this.runCount = 0;
        this.nowLoading = false;
        this.isActive = false;
        this.nextRunTimeout = 0;
        this.startDelayTimeout = 0;
        this.originalDocTitle = document.title;

        document.querySelectorAll('.thread .e-get-replies').forEach(function (elm) {
            elm.addEventListener('click', function (e) {
                e.preventDefault();
                that.runOnce(elm.closest('.thread').dataset.id);
            });
        });
    }

    AutoUpdate.prototype.run = function run(manual) {
        if (this.nowLoading) {
            return false;
        }

        this.nextLoadDelay = this.nextLoadDelay * (this.runCount === 0 ? 1 : this.runCount);
        if (this.nextLoadDelay > 30000) {
            this.nextLoadDelay = 30000;
        }

        // Limit
        if (this.runCount > 40) {
            this.stop();
        }

        if (manual) {
            this.runCount = 0;
            if (this.isActive) {
                this.restart();
            }
        }

        var thread = _YBoard2.default.Thread.getElm(this.threadId);
        var replies = thread.querySelectorAll('.reply');

        var fromId = 0;
        if (replies.length !== 0) {
            fromId = replies[replies.length - 1];
            fromId = fromId.getAttribute('id').replace('post-', '');
        }

        var visibleReplies = [];
        replies.forEach(function (elm) {
            visibleReplies.push(elm.dataset.id);
        });

        this.nowLoading = true;
        var that = this;
        _YQuery2.default.post('/api/thread/getreplies', {
            'threadId': this.threadId,
            'fromId': fromId,
            'newest': true,
            'xhr': function xhr(_xhr) {
                _xhr.responseType = 'document';

                return _xhr;
            },
            'visibleReplies': visibleReplies
        }).onLoad(function (xhr) {
            // Remove deleted replies
            var deletedReplies = xhr.getResponseHeader('X-Deleted-Replies');
            if (deletedReplies !== null) {
                deletedReplies.split(',').forEach(function (id) {
                    var post = document.getElementById('post-' + id);
                    if (post !== null) {
                        post.remove();
                    }
                });
            }

            // Return if there's no new replies and show a notification if we're running manually
            if (xhr.responseText.length === 0) {
                if (manual) {
                    _Toast2.default.info(messages.noNewReplies);
                }

                return;
            }

            var data = document.createElement('div');
            data.classList.add('ajax');
            data.innerHTML = xhr.responseText;

            that.lastUpdateNewReplies = data.querySelectorAll('.post').length;
            that.newReplies += that.lastUpdateNewReplies;

            if (that.lastUpdateNewReplies === 0) {
                ++that.runCount;
            } else {
                that.runCount = 0;
            }

            // Update backlinks
            data.querySelectorAll('.ref').forEach(function (elm) {
                var referredId = elm.dataset.id;
                var referredPost = document.getElementById('post-' + referredId);
                if (referredPost === null) {
                    return true;
                }

                // Create replies-container if it does not exist
                var repliesElm = referredPost.querySelector('.post-replies');
                if (repliesElm === null) {
                    repliesElm = document.createElement('div');
                    repliesElm.classList.add('post-replies');
                    repliesElm.innerHTML = messages.replies + ':';
                    referredPost.appendChild(repliesElm);
                }

                var clone = elm.cloneNode(true);
                clone.innerHTML = clone.innerHTML.replace(' (' + messages.op + ')', '');
                _YBoard2.default.addTooltipEventListener(clone);
                repliesElm.appendChild(document.createTextNode(' '));
                repliesElm.appendChild(clone);
            });

            _YBoard2.default.initElement(data);
            requestAnimationFrame(function () {
                thread.querySelector('.replies').appendChild(data);
            });

            // Run again
            if (!manual) {
                that.nextRunTimeout = setTimeout(function () {
                    that.start();
                }, that.nextLoadDelay);
            }
        }).onError(function (xhr) {
            if (xhr.status === 410) {
                // Thread was deleted
                var _thread = document.getElementById('thread-' + that.threadId);
                if (_thread !== null) {
                    _thread.remove();
                }
            }
            that.stop();
        }).onEnd(function () {
            that.nowLoading = false;

            // Notify about new posts on title
            if (!document.hasFocus() && that.newReplies > 0 && document.body.classList.contains('thread-page')) {
                document.title = '(' + that.newReplies + ') ' + that.originalDocTitle;
            } else {
                if (that.newReplies !== 0) {
                    that.newReplies = 0;
                }
            }
        });
    };

    AutoUpdate.prototype.runOnce = function runOnce(thread) {
        this.threadId = thread;
        this.run(true);
    };

    AutoUpdate.prototype.start = function start() {
        this.isActive = true;
        if (this.startDelayTimeout) {
            clearTimeout(this.startDelayTimeout);
        }

        var that = this;
        this.threadId = $('.thread:first').data('id');
        this.startDelayTimeout = setTimeout(function () {
            that.run(false);
        }, 1000);

        return true;
    };

    AutoUpdate.prototype.stop = function stop() {
        if (!this.isActive) {
            return true;
        }

        if (this.startDelayTimeout) {
            clearTimeout(this.startDelayTimeout);
        }
        this.isActive = false;

        this.reset();
        return true;
    };

    AutoUpdate.prototype.restart = function restart() {
        this.stop();
        this.start();
    };

    AutoUpdate.prototype.reset = function reset() {
        this.nowLoading = false;
        this.newReplies = 0;
        this.runCount = 0;
        if (document.title !== this.originalDocTitle) {
            document.title = this.originalDocTitle;
        }

        if (this.nextRunTimeout) {
            clearTimeout(this.nextRunTimeout);
        }
    };

    return AutoUpdate;
}();

exports.default = AutoUpdate;

/***/ }),
/* 17 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Follow = function () {
    function Follow() {
        _classCallCheck(this, Follow);

        var that = this;

        document.querySelectorAll('.e-thread-follow').forEach(function (elm) {
            elm.addEventListener('click', that.toggle);
        });

        document.querySelectorAll('.e-follow-mark-read').forEach(function (elm) {
            elm.addEventListener('click', that.markAllRead);
        });

        document.querySelectorAll('.thread .e-mark-read').forEach(function (elm) {
            elm.addEventListener('click', that.markRead);
        });
    }

    Follow.prototype.markRead = function markRead(e) {
        var threadId = e.target.closest('.thread').dataset.id;

        e.target.hide();
        document.querySelectorAll('.icon-bookmark .unread-count').forEach(function (elm) {
            var newNotificationCount = parseInt(elm.innerHTML) - 1;
            if (newNotificationCount <= 0) {
                elm.hide();
            } else {
                elm.innerHTML = newNotificationCount;
            }
        });

        _YQuery2.default.post('/api/user/thread/follow/markread', { 'threadId': threadId }).onError(function () {
            e.target.show();

            document.querySelectorAll('.icon-bookmark .unread-count').forEach(function (elm) {
                var newNotificationCount = parseInt(elm.innerHTML) + 1;
                if (newNotificationCount === 1) {
                    elm.show();
                } else {
                    elm.innerHTML = newNotificationCount;
                }
            });
        });
    };

    Follow.prototype.markAllRead = function markAllRead() {
        document.querySelectorAll('.icon-bookmark .unread-count').forEach(function (elm) {
            elm.hide();
        });
        document.querySelectorAll('h3 .notification-count').forEach(function (elm) {
            elm.hide();
        });

        _YQuery2.default.post('/api/user/thread/follow/markallread').onError(function () {
            document.querySelectorAll('.icon-bookmark .unread-count').forEach(function (elm) {
                elm.show();
            });
            document.querySelectorAll('h3 .notification-count').forEach(function (elm) {
                elm.show();
            });
        });
    };

    Follow.prototype.toggle = function toggle(e) {
        var thread = e.target.closest('.thread');
        var button = e.currentTarget;

        var create = true;
        if (e.currentTarget.classList.contains('act')) {
            create = false;
        }
        thread.classList.toggle('followed');

        toggleButton(button);

        _YQuery2.default.post(create ? '/api/user/thread/follow/create' : '/api/user/thread/follow/delete', { 'threadId': thread.dataset.id }).onError(function (xhr) {
            thread.classList.toggle('followed');
            toggleButton(button);
        });

        function toggleButton(elm) {
            if (!elm.classList.contains('act')) {
                elm.classList.add('icon-bookmark-remove', 'act');
                elm.classList.remove('icon-bookmark-add');
            } else {
                elm.classList.add('icon-bookmark-add');
                elm.classList.remove('icon-bookmark-remove', 'act');
            }
        }
    };

    return Follow;
}();

exports.default = Follow;

/***/ }),
/* 18 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _YQuery = __webpack_require__(0);

var _YQuery2 = _interopRequireDefault(_YQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Hide = function () {
    function Hide() {
        _classCallCheck(this, Hide);

        var that = this;

        document.querySelectorAll('.e-thread-hide').forEach(function (elm) {
            elm.addEventListener('click', that.toggle);
        });
    }

    Hide.prototype.toggle = function toggle(e) {
        var thread = e.target.closest('.thread');
        var button = e.currentTarget;

        var create = true;
        if (e.currentTarget.classList.contains('act')) {
            create = false;
        }
        thread.classList.toggle('hidden');

        toggleButton(button);

        _YQuery2.default.post(create ? '/api/user/thread/hide/create' : '/api/user/thread/hide/delete', { 'threadId': thread.dataset.id }).onError(function (xhr) {
            thread.classList.toggle('hidden');
            toggleButton(button);
        });

        function toggleButton(elm) {
            if (!elm.classList.contains('act')) {
                elm.classList.add('icon-eye', 'act');
                elm.classList.remove('icon-eye-crossed');
            } else {
                elm.classList.add('icon-eye-crossed');
                elm.classList.remove('icon-eye', 'act');
            }
        }
    };

    return Hide;
}();

exports.default = Hide;

/***/ })
/******/ ]);