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

    if (button.hasClass('icon-bookmark-add')) {
        button
            .removeClass('icon-bookmark-add')
            .addClass('icon-bookmark-remove')
            .attr('onclick', 'unfollowThread(' + threadId + ')');
    } else {
        button
            .removeClass('icon-bookmark-remove')
            .addClass('icon-bookmark-add')
            .attr('onclick', 'followThread(' + threadId + ')');
    }
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
// Notifications
// -------------------------------------------
$.fn.extend({
    openModal: function(name) {
        // Don't double initialize
        if (this.hasClass('tooltipstered')) {
            return true;
        }

        var closeButton = '<button class="close-modal close-modal-button icon-cross2"></button>';
        var ajax = true;
        var url;
        var data;
        if (name == 'notifications') {
            url = '/scripts/notifications/get';
        } else if (name == 'report') {
            url = '/scripts/report/getform';
        } else {
            data = messages.errorOccurred;
            return false;
        }

        this.tooltipster({
            content: loadingAnimation(),
            side: 'bottom',
            animationDuration: 0,
            updateAnimation: null,
            delay: 0,
            arrow: false,
            contentAsHTML: true,
            theme: 'modal-box',
            zIndex: 1001,
            trigger: 'click',
            interactive: 'true',
            functionReady: function(instance, helper) {
                if (ajax) {
                    $.ajax({
                        url: url,
                        type: "POST"
                    }).done(function (data, textStatus, xhr) {
                        data = $(data);
                        data.find('.datetime').localizeTimestamp(this);
                        data = data.prop('outerHTML');

                        instance.content(closeButton + data);
                        updateUnreadNotificationCount($('.notifications-list .not-read').length);
                    }).fail(function (xhr, textStatus, errorThrown) {
                        var errorMessage = getErrorMessage(xhr, errorThrown);
                        instance.content(closeButton + errorMessage);
                    });
                } else {
                    instance.content(closeButton + data);
                }
                $(helper.tooltip).on('click', '.close-modal', function() {
                    instance.close();
                });
            },
            functionAfter: function(instance, helper) {
                instance.content(closeButton + loadingAnimation());
            }
        }).tooltipster('open')
    }
});

function getNotifications(elm) {
    $(elm).openModal('notifications');
}

function markNotificationRead(id) {
    $('#n-' + id).removeClass('not-read').addClass('is-read');
    $.ajax({
        url: '/scripts/notifications/markread',
        type: "POST",
        data: {'id': id}
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage);
    });

    updateUnreadNotificationCount($('.notification.not-read').length);
}

function markAllNotificationsRead() {
    $.ajax({
        url: '/scripts/notifications/markallread',
        type: "POST"
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage);
    });

    updateUnreadNotificationCount(0);
}

function updateUnreadNotificationCount(count) {
    var elm = $('.unread-notifications');
    elm.html(parseInt(count));

    if (count == 0) {
        elm.addClass('none');
    } else {
        elm.removeClass('none');
    }
}

// -------------------------------------------
// Threadlist search
// -------------------------------------------
function searchThreadlist(word) {
    if (word.length == 0) {
        $('.thread-box').show();
    } else {
        $('.thread-box').hide();
        $('.thread-box').each(function() {
            var self = $(this);
            if (self.find('.post').html().toLowerCase().indexOf(word) !== -1) {
                $(this).show();
            }
        });
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
    if (!document.hasFocus() && newReplies > 0 && $('body').hasClass('thread-page')) {
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
// FIXME: These functions might need reviewing
var postformLocation = $('#post-form').prev();
function showPostForm(isReply) {
    if (typeof isReply == 'undefined') {
        isReply = false;
    }

    if (!isReply) {
        // Reset if we click the "Create thread" -button
        resetPostForm();
    }

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
    $('#file-id').val('');
    $('#file-name').val('');
    postForm.insertAfter(postformLocation);
    postForm.find('.progressbar').each(function() {
        updateProgressBar($(this), 0);
    });

    if (fileUploadXhr !== null) {
        fileUploadXhr.abort();
    }

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
    showPostForm(true);

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
    showPostForm(true);

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

var fileUploadInProgress = false;
var fileUploadXhr = null;
$('#post-files').on('change', function(e) {
    if (!('FormData' in window)) {
        toastr.error(messages.oldBrowserWarning, messages.errorOccurred);
        return false;
    }

    var form = $(e.target);
    var fileInput = $(this);
    var progressBar = fileInput.parent('label').next('.file-progress');

    $('#file-name').val('');
    form.removeData('do-submit');

    // Abort any ongoing uploads
    if (fileUploadXhr !== null) {
        fileUploadXhr.abort();
    }

    progressBar.find('div').css('width', '1%');

    // Calculate upload size and check it does not exceed the set maximum
    var maxSize = fileInput.data('maxsize');
    var fileList = fileInput[0].files;
    var fileSizeSum = 0;
    for (var i = 0, file; file = fileList[i]; i++) {
        fileSizeSum += file.size;
    }

    if (fileSizeSum > maxSize) {
        toastr.warning(messages.maxSizeExceeded);
        return false;
    }

    var fd = new FormData();
    fd.append('file', this.files[0]);

    fileUploadInProgress = true;

    var fileName = $('#post-files').val().split('\\').pop().split('.');
    fileName.pop();
    $('#file-name').val(fileName.join('.'));

    fileUploadXhr = $.ajax({
        url: '/scripts/files/upload',
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
                    if (percent < 1) {
                        percent = 1;
                    } else if (percent > 95) {
                        percent = 95;
                    }
                    updateProgressBar(progressBar, percent);
                }
            }, false);
            return xhr;
        }
    }).always(function () {
        fileUploadInProgress = false;
    }).done(function (data, textStatus, xhr) {
        updateProgressBar(progressBar, 100);
        data = JSON.parse(data);
        if (data.message.length != 0) {
            $('#file-id').val(data.message);

            if (typeof $('#post-form').data('do-submit') != 'undefined') {
                submitPost();
            }
        } else {
            toastr.error(messages.errorOccurred);
            updateProgressBar(progressBar, 0);
        }
    }).fail(function (xhr, textStatus, errorThrown) {
        if (typeof xhr.responseText != 'undefined') {
            var errorMessage = getErrorMessage(xhr, errorThrown);
            toastr.error(errorMessage, messages.errorOccurred);
        }
        updateProgressBar(progressBar, 0);
    });
});

var submitInProgress;
function submitPost(e) {
    if (typeof e != 'undefined') {
        e.preventDefault();
    }

    if (!('FormData' in window)) {
        toastr.error(messages.oldBrowserWarning, messages.errorOccurred);
        return false;
    }

    var form = $('#post-form');
    var submitButton = form.find('input[type="submit"].button');

    // File upload in progress -> wait until done
    if (fileUploadInProgress) {
        submitButton.attr('disabled', true);
        form.data('do-submit', 'true');
        return false;
    }

    // Prevent duplicate submissions by double clicking etc.
    if (submitInProgress) {
        return false;
    }
    submitInProgress = true;

    form.find('#post-files').val('');

    var fd = new FormData(form[0]);

    $.ajax({
        url: form.attr("action"),
        type: "POST",
        processData: false,
        contentType: false,
        data: fd
    }).done(function (data, textStatus, xhr) {
        var dest = $('#post-destination');
        if (dest.attr('name') != 'thread') {
            var thread = null;
        } else {
            var thread = dest.val();
        }

        if (thread != null) {
            toastr.success(messages.postSent);
            getNewReplies(thread, true);
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
        submitButton.removeAttr('disabled');
        submitInProgress = false;

        // Reset captcha if present
        if (typeof grecaptcha != 'undefined') {
            grecaptcha.reset();
        }
    });
}

function updateProgressBar(elm, progress) {
    if (progress < 0) {
        progress = 0;
    } else if (progress > 100) {
        progress = 100;
    }

    if (progress == 0) {
        elm.find('div').css('width', 0).removeClass('in-progress');
    } else {
        elm.find('div').css('width', progress +'%').addClass('in-progress');
    }
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
function switchThemeVariation() {
    var css = $('.css:last');
    var variations = css.data('alt');
    var current = css.data('cur-alt').toString();
    if (typeof variations == 'undefined') {
        return false;
    }

    var keys = Object.keys(variations);
    var next = keys[($.inArray(current, keys) + 1) % keys.length];
    console.log(variations);
    console.log(keys[next]);
    if (typeof keys[next] == 'undefined') {
        next = 0;
    }
    console.log(variations[current]);
    console.log(variations[keys[next]]);

    var oldHref = css.attr('href');
    var newHref = oldHref.replace(variations[current], variations[keys[next]]);
    if (newHref == oldHref) {
        return true;
    }

    $('<link>').attr({
        'rel': 'stylesheet',
        'class': 'css',
        'href': newHref,
        'data-alt': JSON.stringify(variations),
        'data-cur-alt': next,
    }).insertAfter(css);

    var timeout = setTimeout(function(){
        $('.css:first').remove();
    }, 2000);

    $.ajax({
        url: '/scripts/preferences/setthemevariation',
        type: "POST",
        data: {'id': next}
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage);
        clearTimeout(timeout);
    });
}
function toggleHideSidebar() {
    if ($('#hide-sidebar').is('*')) {
        $('#hide-sidebar').remove();
        $('#sidebar').removeClass('visible');
    } else {
        $('<link>').attr({
            'rel': 'stylesheet',
            'id': 'hide-sidebar',
            'href': config.staticUrl + '/css/hide_sidebar.css',
        }).appendTo('head');
    }

    $.ajax({
        url: '/scripts/preferences/togglehidesidebar',
        type: "POST"
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
var reflinkCreateTimeout;
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
    })
    .on('touchstart mouseenter', '.reflink:not(.tooltipstered)', function (e) {
        var elm = $(this);
        reflinkCreateTimeout = setTimeout(function() {
            e.preventDefault();
            var id = elm.data('id');
            var content = loadingAnimation();
            if ($p(id).is('*')) {
                content = $p(id).html();
            }

            elm.tooltipster({
                content: content,
                side: 'bottom',
                animationDuration: 0,
                updateAnimation: null,
                delay: 0,
                arrow: false,
                contentAsHTML: true,
                theme: 'thread',
                trigger: 'custom',
                triggerOpen: {
                    mouseenter: true,
                    touchstart: true
                },
                triggerClose: {
                    mouseleave: true,
                    click: true
                },
                functionInit: function (instance, helper) {
                    var id = $(helper.origin).data('id');
                    $.ajax({
                        url: '/scripts/posts/get',
                        type: "POST",
                        data: {'post_id': id}
                    }).done(function (data, textStatus, xhr) {
                        // Update timestamps
                        data = $(data);
                        data.find('.datetime').localizeTimestamp(this);

                        instance.content(data);
                    }).fail(function (xhr, textStatus, errorThrown) {
                        var errorMessage = getErrorMessage(xhr, errorThrown);
                        instance.content(errorMessage);
                    });
                }
            }).tooltipster('open');
        }, 100);
    })
    .on('touchend mouseleave', '.reflink:not(.tooltipstered)', function (e) {
        clearTimeout(reflinkCreateTimeout);
    })
    .on('click', ':not(.tooltipster-base) .reflink', function (e) {
        var id = $(this).data('id');
        if ($p(id).is('*')) {
            e.preventDefault();
            window.location = window.location.href.split('#')[0] + '#post-' + id;
        }
    });

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

$('body >:not(#topbar):not(#sidebar)').on('click', function (e) {
    $('#sidebar.visible').removeClass('visible');
});

// -------------------------------------------
// Catalog search
// -------------------------------------------
function searchCatalog(word) {
    var threads = $('.thread-box');
    if (word.length == 0) {
        threads.show();
    } else {
        threads.hide();
        threads.each(function() {
            var self = $(this);
            if (self.find('h3').html().toLowerCase().indexOf(word.toLowerCase()) !== -1) {
                $(this).show();
                return true;
            }
            if (self.find('.post').html().toLowerCase().indexOf(word.toLowerCase()) !== -1) {
                $(this).show();
                return true;
            }
        });
    }
}

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
}).on('hashchange load', function (e) {
    if (e.type == 'hashchange') {
        removeHighlights();
    }
    if (window.location.hash.length != 0) {
        highlightPost(window.location.hash);
        // Prevent posts going under the top bar
        // FIXME: Hacky... Causes slight page jumping. Not good.
        if ($('#topbar').is(':visible')) {
            var post = $(window.location.hash);
            $(window).scrollTop(post.offset().top - $('#topbar').height());
        }
    }
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
