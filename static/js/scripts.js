// CSRF token to each request and disable caching
$.ajaxSetup({
    cache: false,
    headers: {
        'X-CSRF-Token': config.csrfToken
    }
});

// -------------------------------------------
// jQuery plugins
// -------------------------------------------
jQuery.fn.extend({
    insertAtCaret: function (before, after) {
        if (typeof after == 'undefined') {
            after = '';
        }

        return this.each(function () {
            if (document.selection) {
                // IE
                var sel = document.selection.createRange();
                sel.text = before + sel.text + after;
                this.focus();
            } else if (this.selectionStart || this.selectionStart == '0') {
                // FF & Chrome
                var selectedText = this.value.substr(this.selectionStart, (this.selectionEnd - this.selectionStart));
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
        });
    },
    localizeTimestamp: function () {
        return this.each(function () {
            this.innerHTML = new Date(this.innerHTML.replace(' ', 'T') + 'Z').toLocaleString();
        });
    },
    localizeNumber: function () {
        return this.each(function () {
            this.innerHTML = parseFloat(this.innerHTML).toLocaleString();
        });
    },
    localizeCurrency: function () {
        return this.each(function () {
            this.innerHTML = parseFloat(this.innerHTML).toLocaleString(true, {
                'style': 'currency',
                'currency': 'eur'
            });
        });
    }
});
jQuery.fn.reverse = [].reverse;

// -------------------------------------------
// Post deletion
// -------------------------------------------
function deletePost(id) {
    if (!confirm(messages.confirmDelete)) {
        return false;
    }

    $.ajax({
        url: '/scripts/posts/delete',
        type: "POST",
        data: {'post_id': id}
    }).done(function (data, textStatus, xhr) {
        $p(id).remove();
        if ($('body').hasClass('thread-page')) {
            if ($t(id).is('*')) {
                // We're in the thread we just deleted
                returnToBoard();
            }
        } else {
            // The deleted post is not the current thread
            $t(id).remove();
            toastr.success(messages.postDeleted);
        }
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage, messages.errorOccurred);
    });
}

// -------------------------------------------
// Thread following
// -------------------------------------------
function followThread(id) {
    toggleFollowButton(id);
    $.ajax({
        url: '/scripts/threads/follow',
        type: "POST",
        data: {'thread_id': id}
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage, messages.errorOccurred);
        toggleFollowButton(id);
    });
}

function unfollowThread(id) {
    toggleFollowButton(id);
    $.ajax({
        url: '/scripts/threads/unfollow',
        type: "POST",
        data: {'thread_id': id}
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage, messages.errorOccurred);
        toggleFollowButton(id);
    });
}

function toggleFollowButton(threadId) {
    var button = $t(threadId).find('.followbutton');
    button.toggleClass('icon-bookmark-add').toggleClass('icon-bookmark-remove');
}

// -------------------------------------------
// Thread hiding
// -------------------------------------------
function hideThread(id) {
    $.ajax({
        url: '/scripts/threads/hide',
        type: "POST",
        data: {'thread_id': id}
    }).done(function (data, textStatus, xhr) {
        $t(id).fadeOut();
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage, messages.errorOccurred);
    });
}

function restoreThread(id) {
    $.ajax({
        url: '/scripts/threads/restore',
        type: "POST",
        data: {'thread_id': id}
    }).done(function (data, textStatus, xhr) {
        $t(id).fadeOut();
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage, messages.errorOccurred);
    });
}

// -------------------------------------------
// Signup form in sidebar
// -------------------------------------------
function signupForm(elm, e) {
    e.preventDefault();
    elm = $(elm);

    if (typeof grecaptcha != 'undefined' && $('#signup-captcha').html().length == 0) {
        grecaptcha.render('signup-captcha', {
            'sitekey': config.reCaptchaPublicKey,
            'size': 'compact',
            'theme': 'dark'
        });
    }

    var form = $('#login');
    var signupForm = $('#signup-form');

    if (typeof form.data('login') == 'undefined') {
        form.data('login', form.attr('action'));
    }

    if (!elm.data('open')) {
        form.attr('action', form.data('signup'));
        elm.html(messages.cancel);
        $('#loginbutton').val(messages.signUp);
        signupForm.slideDown();
        elm.data('open', true);
    } else {
        form.attr('action', form.data('login'));
        elm.html(messages.signUp);

        $('#loginbutton').val(messages.logIn);
        signupForm.slideUp();
        signupForm.find('input').val('');
        elm.data('open', false);
    }
}

// -------------------------------------------
// Thread inline expansion
// -------------------------------------------
function getMoreReplies(threadId) {
    var thread = $t(threadId);
    if (!thread.hasClass('expanded')) {
        // Expand
        thread.addClass('expanded', true);

        var fromId = thread.find('.reply:first').attr('id').replace('post-', '');

        $.ajax({
            url: '/scripts/threads/getreplies',
            type: "POST",
            data: {
                'thread_id': threadId,
                'from_id': fromId
            }
        }).done(function (data, textStatus, xhr) {
            // Update timestamps
            data = $(data);
            data.find('.datetime').localizeTimestamp(this);

            $t(threadId).find('.more-replies-container').html(data);
        }).fail(function (xhr, textStatus, errorThrown) {
            var errorMessage = getErrorMessage(xhr, errorThrown);
            toastr.error(errorMessage);
        });
    } else {
        // Contract
        $t(threadId).removeClass('expanded').find('.more-replies-container').html('');
    }
}

// -------------------------------------------
// Thread ajax reply update
// -------------------------------------------
// FIXME: PLEASE do this better. This sucks ass.
var newReplies = 0;
var lastUpdateNewReplies = 0;
var updateCount = 0;
var loadingReplies = false;
var updateRunning = false;
var nextUpdateTimeout = false;
var documentTitle = document.title;
function getNewReplies(threadId, manual) {
    if (loadingReplies) {
        return false;
    }

    loadingReplies = true;
    if (typeof manual == 'undefined') {
        manual = false;
    }
    if (manual) {
        updateCount = 0;
        if (updateRunning) {
            stopAutoUpdate();
            startAutoUpdate();
        }
    }

    var thread = $t(threadId);
    var fromId = thread.find('.reply:last').attr('id');
    if (typeof fromId == 'undefined') {
        fromId = 0;
    } else {
        fromId = fromId.replace('post-', '');
    }

    $.ajax({
        url: '/scripts/threads/getreplies',
        type: "POST",
        data: {
            'thread_id': threadId,
            'from_id': fromId,
            'newest': true,
        }
    }).done(function (data, textStatus, xhr) {
        if (manual && data.length == 0) {
            toastr.info(messages.noNewReplies);
        }
        // Update timestamps
        data = $(data);
        data.find('.datetime').localizeTimestamp(this);

        lastUpdateNewReplies = data.find('.message').length;
        newReplies += lastUpdateNewReplies;

        data.appendTo(thread.find('.replies'));
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage);
        stopAutoUpdate();
    }).always(function () {
        setTimeout('loadingReplies = false', 100);
        updateAutoUpdateVars();
    });
}

if ($('body').hasClass('thread-page')) {
    var thread = $('.thread:first').data('id');
    $(window)
        .on('scroll', function () {
            var windowBottom = $(window).height() + $(window).scrollTop();
            var repliesBottom = $('.replies').offset().top + $('.replies').height();
            if (windowBottom > repliesBottom) {
                if (!updateRunning && !$('#post-message').is(':focus')) {
                    updateRunning = true;
                    startAutoUpdate();
                }
            } else {
                if (updateRunning) {
                    stopAutoUpdate();
                    updateRunning = false;
                }
            }
        })
        .on('focus', function () {
            newReplies = 0;
            updateCount = 0;
            if (document.title != documentTitle) {
                document.title = documentTitle;
            }
        });
    var startTimeout;
    $('#post-message')
        .on('focus', function () {
            clearTimeout(startTimeout);
            stopAutoUpdate();
        })
        .on('blur', function () {
            startTimeout = setTimeout('startAutoUpdate()', 500);
        });
}

function updateAutoUpdateVars() {
    if (lastUpdateNewReplies == 0) {
        ++updateCount;
    } else {
        updateCount = 0;
    }

    // Notify about new posts on title
    if (!document.hasFocus() && newReplies > 0) {
        document.title = '(' + newReplies + ') ' + documentTitle;
        var replies = $('.replies');
        replies.find('hr').remove();
        replies.find('.reply:eq(-' + newReplies + ')').before('<hr>');
    } else if (newReplies != 0) {
        newReplies = 0;
    }
}

function startAutoUpdate() {
    getNewReplies(thread);

    var timeout = 2000;
    timeout = timeout * (updateCount == 0 ? 1 : updateCount);
    if (timeout > 30000) {
        timeout = 30000;
    }

    // Limit
    if (updateCount > 40) {
        return false;
    }

    // Run again
    nextUpdateTimeout = setTimeout(function () {
        startAutoUpdate();
    }, timeout);
}

function stopAutoUpdate() {
    clearTimeout(nextUpdateTimeout);
}

// -------------------------------------------
// Functions related to post form
// -------------------------------------------
var postformLocation = $('#post-form').prev();
function showPostForm() {
    var form = $('#post-form');
    form.addClass('visible');
    var textarea = $('#post-message');
    if (textarea.is(':visible')) {
        textarea.focus();
    }
}

function hidePostForm() {
    $('#post-form').removeClass('visible');
}

function resetPostForm() {
    var postForm = $('#post-form');
    postForm[0].reset();
    postForm.insertAfter(postformLocation);

    resetOriginalPostFormDestination();
    hidePostForm();
}

function addBbCode(code) {
    $('#post-message').insertAtCaret('[' + code + ']', '[/' + code + ']');
}

function toggleBbColorBar() {
    $('#color-buttons').toggle();
    $('#post-message').focus();
}

function replyToThread(id) {
    var postForm = $('#post-form');
    postForm.appendTo('#thread-' + id + ' .thread-content');
    showPostForm();

    saveOriginalPostFormDestination();
    $('#post-destination').attr('name', 'thread').val(postForm.closest('.thread').data('id'));

    $('#post-message').focus();
}

function replyToPost(id, newline) {
    var selectedText = getSelectionText();

    if (typeof newline == 'undefined') {
        newline = true;
    }

    var postForm = $('#post-form');
    postForm.appendTo('#post-' + id);
    showPostForm();

    saveOriginalPostFormDestination();
    $('#post-destination').attr('name', 'thread').val(postForm.closest('.thread').data('id'));

    var textarea = $('#post-message');
    textarea.focus();

    var append = '';
    if (textarea.val().substr(-1) == '\n') {
        append += '\n';
    } else if (textarea.val().length != 0 && newline) {
        append += '\n\n';
    }
    append += '>>' + id + '\n';

    // If any text on the page was selected, add it to post form with quotes
    if (selectedText != '') {
        append += '>' + selectedText.replace(/(\r\n|\n|\r)/g, '$1>') + '\n';
    }

    textarea.val(textarea.val().trim() + append);
}

function saveOriginalPostFormDestination() {
    var destElm = $('#post-destination');

    // Hide board selector
    if ($('#label-board').is('*')) {
        $('#label-board').hide().find('select').removeAttr('required');
        return true;
    }

    if (typeof destElm.data('orig-name') != 'undefined') {
        return true;
    }

    destElm.data('orig-name', destElm.attr('name'));
    destElm.data('orig-value', destElm.val());

    return true;
}

function resetOriginalPostFormDestination() {
    var destElm = $('#post-destination');

    // Restore board selector
    if ($('#label-board').is('*')) {
        $('#label-board').show().find('select').attr('required', true);
        destElm.removeAttr('name').removeAttr('value');
    }

    if (typeof destElm.data('orig-name') == 'undefined') {
        return true;
    }

    // In a thread or a board
    destElm.attr('name', destElm.data('orig-name'));
    destElm.val(destElm.data('orig-value'));

    return true;
}

var submitInProgress;
function submitPost(e) {
    e.preventDefault();

    if (!('FormData' in window)) {
        toastr.error(messages.oldBrowserWarning, messages.errorOccurred);
        return false;
    }

    // Prevent duplicate submissions by double clicking etc.
    if (submitInProgress) {
        return false;
    }
    submitInProgress = true;

    var form = $(e.target);
    var fileInput = form.find('input:file');

    // Calculate upload size and check it does not exceed the set maximum
    var maxSize = fileInput.data('maxsize');
    var fileList = fileInput[0].files;
    var fileSizeSum = 0;
    for (var i = 0, file; file = fileList[i]; i++) {
        fileSizeSum += file.size;
    }

    if (fileSizeSum > maxSize) {
        toastr.warning(messages.maxSizeExceeded);
        submitInProgress = false;
        return false;
    }

    var fd = new FormData(form[0]);

    $.ajax({
        url: form.attr("action"),
        type: "POST",
        processData: false,
        contentType: false,
        data: fd,
        xhr: function () {
            var xhr = $.ajaxSettings.xhr();
            if (!xhr.upload) {
                return xhr;
            }
            xhr.upload.addEventListener('progress', function (evt) {
                if (evt.lengthComputable) {
                    var percent = Math.round((evt.loaded / evt.total) * 100);
                    $('#post-progress').find('div').css('width', percent + '%');
                }
            }, false);
            return xhr;
        }
    }).done(function (data, textStatus, xhr) {
        var dest = $('#post-destination');
        if (dest.attr('name') != 'thread') {
            var thread = null;
        } else {
            var thread = dest.val();
        }

        if (thread != null) {
            toastr.success(messages.postSent);
            getNewReplies(thread);
        } else if (data.length == 0) {
            pageReload();
        } else {
            data = JSON.parse(data);
            if (typeof data.message == 'undefined') {
                toastr.error(messages.errorOccurred);
            } else {
                window.location = '/' + dest.val() + '/' + data.message;
            }
        }

        // Reset post form
        resetPostForm();
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage, messages.errorOccurred);
    }).always(function () {
        $('#post-progress').find('div').css('width', '');
        submitInProgress = false;

        // Reset captcha if present
        if (typeof grecaptcha != 'undefined') {
            grecaptcha.reset();
        }
    });
}

// -------------------------------------------
// Media player
// -------------------------------------------
function playMedia(elm, e) {
    e.preventDefault();
    stopAllMedia();

    var link = $(elm);
    var container = link.parent();
    var post = container.parent('.message');
    var img = link.find('img');

    var fileId = container.data('id');

    if (typeof link.data('loading') != 'undefined') {
        return false;
    }

    link.data('loading', 'true');

    var loading = setTimeout(function () {
        img.after(loadingAnimation('overlay bottom left'));
    }, 200);

    $.ajax({
        url: '/scripts/files/getmediaplayer',
        type: "POST",
        data: {'file_id': fileId}
    }).done(function (xhr, textStatus, errorThrown) {

        container.removeClass('thumbnail').addClass('media-player-container');
        post.addClass('full');
        container.prepend(xhr);

        var volume = getStoredVal('videoVolume');
        if (volume != null) {
            container.find('video').prop('volume', volume);
        }
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        if (xhr.status == '418') {
            toastr.info(errorMessage);
        } else {
            toastr.error(errorMessage);
        }
    }).always(function () {
        clearTimeout(loading);
        container.find('.loading').remove();
        link.removeData('loading');
    });
}

function stopAllMedia() {
    $('.media-player-container').each(function () {
        var self = $(this);
        var mediaPlayer = self.find('.media-player');

        mediaPlayer.find('video').trigger('pause');
        mediaPlayer.remove();

        self.removeClass('media-player-container').addClass('thumbnail');
    });
}

// Volume save
function saveVolume(elm) {
    storeVal('videoVolume', $(elm).prop("volume"));
}

// -------------------------------------------
// Expand images
// -------------------------------------------
function expandImage(elm, e) {
    e.preventDefault();
    var container = $(elm).parent();
    var post = container.parent('.message');
    var img = $(elm).find('img');

    if (typeof img.data('expanding') != 'undefined') {
        return true;
    }

    post.addClass('full');

    if (typeof img.data('orig-src') == 'undefined') {
        img.data('orig-src', img.attr('src'));
        changeSrc(img, container.find('figcaption a').attr('href'));
        container.removeClass('thumbnail');
    } else {
        changeSrc(img, img.data('orig-src'));
        img.removeData('orig-src');
        container.addClass('thumbnail');
    }

    // Scroll to top of image
    var elmTop = container.offset().top;
    if ($(document).scrollTop() > elmTop) {
        $(document).scrollTop(elmTop);
    }
}

function changeSrc(img, src) {
    img.data('expanding', 'true');
    var loading = setTimeout(function () {
        img.after(loadingAnimation('overlay center'));
    }, 200);
    img.attr('src', src).on('load', function () {
        img.removeData('expanding');
        clearTimeout(loading);
        img.parent().find('.loading').remove();
    });
}

// -------------------------------------------
// User profile related
// -------------------------------------------
function destroySession(sessionId) {
    $.ajax({
        url: '/scripts/user/destroysession',
        type: "POST",
        data: {'session_id': sessionId}
    }).done(function (xhr, textStatus, errorThrown) {
        $('#' + sessionId).fadeOut();
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage);
    });
}

function changeUsername(e) {
    e.preventDefault();

    var form = $(e.target);
    var newName = form.find('input[name="newname"]').val();
    var password = form.find('input[name="password"]').val();

    $.ajax({
        url: form.attr('action'),
        type: "POST",
        data: {
            'new_name': newName,
            'password': password
        }
    }).done(function (xhr, textStatus, errorThrown) {
        pageReload();
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage);
    });
}

function changePassword(e) {
    e.preventDefault();

    var form = $(e.target);
    var newPassword = form.find('input[name="newpass"]').val();
    var newPasswordRe = form.find('input[name="newpassre"]').val();
    var password = form.find('input[name="password"]').val();

    if (newPassword != newPasswordRe) {
        toastr.error(messages.passwordsDoNotMatch);
        return false;
    }

    $.ajax({
        url: form.attr('action'),
        type: "POST",
        data: {
            'new_password': newPassword,
            'password': password
        }
    }).done(function (xhr, textStatus, errorThrown) {
        toastr.success(messages.passwordChanged);
        e.target.reset();
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage);
    });
}

// -------------------------------------------
// Theme switcher
// -------------------------------------------
function toggleDarkTheme() {
    $('<div id="css-loading">' + messages.loading + '</div>').appendTo('body')
        .css({
            'position': 'fixed',
            'top': 0,
            'right': 0,
            'bottom': 0,
            'left': 0,
            'background-color': '#eee',
            'text-align': 'center',
            'padding-top': '20%',
        });

    var css = $('#css');
    var newHref = css.data('alt');
    //css.data('alt', css.attr('href'));

    $('<link>').attr({
        'rel': 'stylesheet',
        'id': 'css',
        'href': newHref,
        'data-alt': css.attr('href'),
    })
        .on('load', function () {
            $('#css').remove();
            $('#css-loading').html('').fadeOut(200, function () {
                this.remove();
            });
        })
        .appendTo('head');

    $.ajax({
        url: '/scripts/preferences/toggledarktheme',
        type: "POST",
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage);
    });
}

// -------------------------------------------
// Localize dates and numbers
// -------------------------------------------
$('.datetime').localizeTimestamp();
$('.number').localizeNumber();
$('.currency').localizeCurrency();

// -------------------------------------------
// Spoilers & reflinks
// -------------------------------------------
var reflinkTooltipTimeout;
$('body:not(.board-catalog)')
    .on('touchstart', '.spoiler:not(.spoiled)', function (e) {
        e.preventDefault();
        $(this).addClass('spoiled');
    })
    .on('click', function (e) {
        $('.spoiler.spoiled').removeClass('spoiled');
    })
    .on('contextmenu', '.reflink', function (e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    })
    .on('touchstart mouseenter', '.reflink', function (e) {
        var elm = this;
        reflinkTooltipTimeout = setTimeout(function () {
            addReflinkTooltip(elm);
            $(elm).tooltipster('open');
        }, 100);
    })
    .on('click touchend mouseleave', '.reflink', function (e) {
        clearTimeout(reflinkTooltipTimeout);
        if ($(this).hasClass('tooltipstered')) {
            $(this).tooltipster('close');
        }
    })
    .on('click', '.reflink', function (e) {
        var id = $(this).data('id');
        if ($p(id).is('*')) {
            e.preventDefault();
            window.location = window.location.href.split('#')[0] + '#post-' + id;
        }
    });

function addReflinkTooltip(elm) {
    var elm = $(elm);
    // Don't double initialize
    if (elm.hasClass('tooltipstered')) {
        return true;
    }

    elm.tooltipster({
        content: loadingAnimation(),
        side: 'bottom',
        animationDuration: 0,
        updateAnimation: null,
        delay: 0,
        arrow: false,
        contentAsHTML: true,
        theme: 'thread',
        trigger: 'custom',
    }).tooltipster('open');
    var id = elm.data('id');

    if ($p(id).is('*')) {
        elm.tooltipster('content', $p(id).html());
    } else {
        $.ajax({
            url: '/scripts/posts/get',
            type: "POST",
            data: {'post_id': id}
        }).done(function (data, textStatus, xhr) {
            // Update timestamps
            data = $(data);
            data.find('.datetime').localizeTimestamp(this);

            elm.tooltipster('content', data);
        }).fail(function (xhr, textStatus, errorThrown) {
            var errorMessage = getErrorMessage(xhr, errorThrown);
            elm.tooltipster('content', errorMessage);
        });
    }
}

// -------------------------------------------
// Mobile menu
// -------------------------------------------
function toggleSidebar() {
    $('#sidebar').toggleClass('visible');
}
$('#sidebar').click(function (e) {
    if (e.offsetX > $('#sidebar').innerWidth()) {
        toggleSidebar();
    }
});

// -------------------------------------------
// Post higlighting
// -------------------------------------------
function highlightPost(id) {
    $(id).addClass('highlighted');
}
function removeHighlights() {
    $('.highlighted').removeClass('highlighted');
}

// -------------------------------------------
// Window and body event bindings
// -------------------------------------------
$(window).on('beforeunload', function (e) {
    return confirmUnload(e);
}).on('hashchange load', function () {
    removeHighlights();
    highlightPost(window.location.hash);
}).on('keydown', function (e) {
    // This brings down the server load quite a bit, as not everything is reloaded when pressing F5
    if (e.which == 116 && !e.ctrlKey) { // F5
        pageReload();
        return false;
    } else if (e.which == 82 && e.ctrlKey && !e.shiftKey) { // R
        pageReload();
        return false;
    }
});

// -------------------------------------------
// "Private" functions used by other functions
// -------------------------------------------
function pageReload() {
    window.location = window.location.href.split('#')[0];
}

function returnToBoard() {
    // Remove everything after the last slash and redirect
    // Should work if we are in a thread, otherwise not really
    var url = window.location.href;
    url = url.substr(0, url.lastIndexOf('/') + 1);

    window.location = url;
}

function getErrorMessage(xhr, errorThrown) {
    if (xhr.responseText.length != 0) {
        try {
            var text = JSON.parse(xhr.responseText);
            errorThrown = text.message;
        } catch (e) {
        }
    }
    return errorThrown;
}

function loadingAnimation(classes) {
    if (typeof classes == 'undefined') {
        classes = '';
    } else {
        classes += ' ';
    }

    return '<span class="' + classes + 'loading icon-loading spin"></span>';
}

function getSelectionText() {
    var text = '';
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    return text;
}

function $t(id) {
    return $('#thread-' + id);
}

function $p(id) {
    return $('#post-' + id);
}

function oldBrowserWarning() {
    toastr.warning(messages.oldBrowserWarning);
}

// -------------------------------------------
// Confirm page exit when there's text in the post form
// -------------------------------------------
function confirmUnload() {
    var textarea = $('#post-message');
    if (!submitInProgress && textarea.is(':visible') && textarea.val().length != 0) {
        return messages.confirmUnload;
    } else {
        e = null;
    }
}

// -------------------------------------------
// LocalStorage wrappers
// -------------------------------------------
function storeVal(key, val) {
    if (typeof localStorage == 'undefined') {
        oldBrowserWarning();
        return false;
    }

    return localStorage.setItem(key, val);
}

function getStoredVal(key) {
    if (typeof localStorage == 'undefined') {
        oldBrowserWarning();
        return false;
    }

    return localStorage.getItem(key);
}

function removeStoredVal(key) {
    if (typeof localStorage == 'undefined') {
        oldBrowserWarning();
        return false;
    }

    return localStorage.removeItem(key);
}
