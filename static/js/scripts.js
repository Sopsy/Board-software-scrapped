var submitInProgress;

// CSRF token to each request and disable caching
$.ajaxSetup({
    cache: false,
    headers: {
        'X-CSRF-Token': config.csrfToken
    }
});

// Post deletion
function deletePost(id) {
    if (!confirm(messages.confirmDelete)) {
        return false;
    }

    $.ajax({
        url: '/scripts/posts/delete',
        type: "POST",
        data: {'postId': id}
    }).done(function (data, textStatus, xhr) {
        $p(id).remove();
        $t(id).remove();
        toastr.success(messages.postDeleted);
    }).fail(function (xhr, textStatus, errorThrown) {
        var errorMessage = getErrorMessage(xhr, errorThrown);
        toastr.error(errorMessage, messages.errorOccurred);
    });
}

// Signup form in sidebar
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

    var signupForm = $('#signup-form');

    if (!elm.data('open')) {

        elm.html(messages.cancel);

        $('#loginbutton').attr('name', 'signup').val(messages.signUp);
        signupForm.slideDown();
        elm.data('open', true);
    } else {
        elm.html(messages.signUp);

        $('#loginbutton').attr('name', 'login').val(messages.logIn);
        signupForm.slideUp();
        signupForm.find('input').val('');
        elm.data('open', false);
    }
}

// Thread inline expansion
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
                'threadId': threadId,
                'fromId': fromId
            }
        }).done(function (data, textStatus, xhr) {
            // Update timestamps
            data = $(data);
            data.find('.datetime').each(function () {
                localizeTimestamp(this);
            });

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

// Too long posts
//$('#your_div')[0].scrollHeight

// Load captcha
function renderCaptcha() {
    grecaptcha.render('post-form-captcha', {
        'sitekey': config.reCaptchaPublicKey
    });
}

// Functions related to post form
var postformLocation = $('#post-form').prev();
function showPostForm() {
    var form = $('#post-form');
    form.addClass('visible');
    var textarea = form.find('textarea');
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
    $('#post-form').find('textarea').insertAtCaret('[' + code + ']', '[/' + code + ']');
}

function replyToThread(id) {
    var postForm = $('#post-form');
    postForm.appendTo('#thread-' + id + ' .thread-content');
    showPostForm();

    saveOriginalPostFormDestination();
    $('#post-destination').attr('name', 'thread').val(postForm.closest('.thread').data('id'));

    postForm.find('textarea').focus();
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

    var textarea = postForm.find('textarea');
    textarea.focus();

    var append = '';
    if (textarea.val().substr(-1) == '\n') {
        append += ' ';
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

    if (typeof destElm.data('orig-name') != 'undefined') {
        return true;
    }

    destElm.data('orig-name', destElm.attr('name'));
    destElm.data('orig-value', destElm.val());

    return true;
}

function resetOriginalPostFormDestination() {
    var destElm = $('#post-destination');

    if (typeof destElm.data('orig-name') == 'undefined') {
        return true;
    }

    destElm.attr('name', destElm.data('orig-name'));
    destElm.val(destElm.data('orig-value'));

    return true;
}

function submitPost(e) {
    e.preventDefault();

    // Prevent duplicate submissions by double clicking etc.
    if (submitInProgress) {
        return false;
    }
    submitInProgress = true;

    var form = $('#post-form');
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
        location.reload();
        toastr.success(messages.postSent);

        // TODO: replace with ajax load of new messages
        // TODO: If new thread, go to thread instead
        //window.location = window.location;


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

// Expand images
function expandImage(elm, e) {
    e.preventDefault();
    var container = $(elm).parent();
    var post = container.parent('.post');
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

// Dates in posts
$('.datetime').each(function () {
    localizeTimestamp(this);
});

function localizeTimestamp(elm) {
    var date = new Date($(elm).html());
    $(elm).html(date.toLocaleString());
}

// Confirm page exit when there's text in the post form
$(window).on('beforeunload', function (e) {
    var textarea = $('#post-form').find('textarea');
    if (!submitInProgress && textarea.is(':visible') && textarea.val().length != 0) {
        return true;
    } else {
        e = null;
    }
});

// Reflinks
$('body').on('click', '.reflink', function (e) {
    var id = $(this).data('id');
    if ($p(id).is('*')) {
        e.preventDefault();
        window.location = window.location.href.split('#')[0] + '#post-' + id;
    }
});

$('body').on('mouseenter', '.reflink:not(.tooltipstered)', function () {
    var elm = $(this);
    elm.tooltipster({
        content: loadingAnimation(),
        side: 'bottom',
        animationDuration: 0,
        delay: [50, 0],
        arrow: false,
        contentAsHTML: true,
        theme: 'thread'
    }).tooltipster('open');
    var id = elm.data('id');

    if ($p(id).is('*')) {
        elm.tooltipster('content', $p(id).html());
    } else {
        $.ajax({
            url: '/scripts/posts/get',
            type: "POST",
            data: {'postId': id}
        }).done(function (data, textStatus, xhr) {
            // Update timestamps
            data = $(data);
            data.find('.datetime').each(function () {
                localizeTimestamp(this);
            });

            elm.tooltipster('content', data);
        }).fail(function (xhr, textStatus, errorThrown) {
            var errorMessage = getErrorMessage(xhr, errorThrown);
            elm.tooltipster('content', errorMessage);
        });
    }
});

// Jquery plugins etc
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
                // Nothing selected, append
                this.value += before + after;
                this.focus();
            }
        })
    }
});

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
    }

    return '<img class="' + classes + 'loading" src="' + config.staticUrl + '/img/loading.gif" alt="">';
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
