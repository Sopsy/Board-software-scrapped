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
    $.ajax({
        url: '/scripts/posts/delete',
        type: "POST",
        processData: false,
        contentType: false,
        data: {'postId': id}
    }).done(function (data, textStatus, xhr) {
        $$(id).remove();
        toastr.success(messages.postDeleted);
    }).fail(function (xhr, textStatus, errorThrown) {
        if (xhr.responseText.length != 0) {
            try {
                var text = JSON.parse(xhr.responseText);
                errorThrown = text.message;
            } catch (e) {
            }
        }
        toastr.error(errorThrown, messages.errorOccurred);
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

    var signupform = $('#signupform');

    if (!elm.data('open')) {

        elm.html(messages.cancel);

        $('#loginbutton').attr('name', 'signup').val(messages.signUp);
        signupform.slideDown();
        elm.data('open', true);
    } else {
        elm.html(messages.signUp);

        $('#loginbutton').attr('name', 'login').val(messages.logIn);
        signupform.slideUp();
        signupform.find('input').val('');
        elm.data('open', false);
    }
}

// Functions related to post form
var postformLocation = $('#post-form').prev();
function showPostForm() {
    // Load captcha
    if (typeof grecaptcha != 'undefined' && $('#post-form-captcha').html().length == 0) {
        grecaptcha.render('postform-captcha', {
            'sitekey': config.reCaptchaPublicKey
        });
    }

    var form = $('#post-form');
    form.show();
    $('.toggle-postform').hide();
    var textarea = form.find('textarea');
    if (textarea.is(':visible')) {
        textarea.focus();
    }
}

function hidePostForm() {
    $('#post-form').hide();
    $('.toggle-postform').show();
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
        toastr.success(messages.postSent);

        // TODO: replace with ajax load of new messages
        // TODO: If new thread, go to thread instead
        window.location = window.location;

        // Reset captcha if present
        if (typeof grecaptcha != 'undefined') {
            grecaptcha.reset();
        }

        // Reset post form
        resetPostForm();
    }).fail(function (xhr, textStatus, errorThrown) {
        if (xhr.responseText.length != 0) {
            try {
                var text = JSON.parse(xhr.responseText);
                errorThrown = text.message;
            } catch (e) {
            }
        }
        toastr.error(errorThrown, messages.errorOccurred);
    }).always(function () {
        $('#post-progress').find('div').css('width', '');
        submitInProgress = false;
    });
}

// Expand images
function expandImage(elm, e) {
    e.preventDefault();
    var container = $(elm).parent();
    var img = $(elm).find('img');

    if (typeof img.data('expanding') != 'undefined') {
        return true;
    }

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
        img.after('<img class="overlay center loading" src="' + config.staticUrl + '/img/loading.gif" alt="">');
    }, 200);
    img.attr('src', src).on('load', function () {
        img.removeData('expanding');
        clearTimeout(loading);
        img.parent().find('.loading').remove();
    });
}

// Dates in posts
$('.datetime').each(function () {
    var date = new Date($(this).html());
    $(this).html(date.toLocaleString());
});

// Confirm page exit when there's text in the post form
$(window).on('beforeunload', function (e) {
    var textarea = $('#post-form').find('textarea');
    if (!submitInProgress && textarea.is(':visible') && textarea.val().length != 0) {
        return true;
    } else {
        e = null;
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

function getSelectionText() {
    var text = '';
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    return text;
}

function $$(id) {
    return $('#post-' + id);
}
