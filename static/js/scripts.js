var submitInProgress;

// CSRF token to each request and disable caching
$.ajaxSetup({
    cache: false,
    headers: {
        'X-CSRF-Token': config.csrfToken
    }
});

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
        cache: false,
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
        toastr.success(messages.messageSent);

        // TODO: replace with ajax load of new messages
        window.location = window.location;

        // Reset captcha if present
        if (typeof grecaptcha != 'undefined') {
            grecaptcha.reset();
        }

        // Reset post form
        form[0].reset();
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

function togglePostForm() {
    if (typeof grecaptcha != 'undefined' && $('#post-form-captcha').html().length == 0) {
        grecaptcha.render('postform-captcha', {
            'sitekey': config.reCaptchaPublicKey
        });
    }

    var form = $('#post-form');
    form.toggle();
    $('.toggle-postform').toggle();
    var textarea = form.find('textarea');
    if (textarea.is(':visible')) {
        textarea.focus();
    }
}

function addBbCode(code) {
    $('#post-form').find('textarea').insertAtCaret('[' + code + ']', '[/' + code + ']');
}

function replyToPost(id, newline) {
    if (typeof newline == 'undefined') {
        newline = true;
    }

    var postForm = $('#post-form');
    postForm.appendTo('#post-' + id).show();

    saveOriginalPostFormDestination();

    $('#post-destination').attr('name', 'thread').val(postForm.closest('.thread').data('id'));

    var textarea = postForm.find('textarea');
    textarea.focus();

    var append = '';
    if (textarea.val().length != 0 && newline) {
        append += '\n\n';
    }
    append += '>>' + id + '\n';

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

function resetPostForm() {
    var postForm = $('#post-form');
    postForm.insertAfter('.board-navigation:first');
    postForm[0].reset();
    resetOriginalPostFormDestination();
    postForm.hide();
}

$('.datetime').each(function () {
    var date = new Date($(this).html());
    $(this).html(date.toLocaleString());
});

jQuery.fn.extend({
    insertAtCaret: function (before, after) {
        if (typeof after == 'undefined') {
            after = '';
        }

        return this.each(function (i) {
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

$(window).on('beforeunload', function(e) {
    var textarea = $('#post-form').find('textarea');
    if (!submitInProgress && textarea.is(':visible') && textarea.val().length != 0) {
        return true;
    }
    else {
        e = null;
    }
});

$('.options-menu').click(function(){
    $('.options-menu').find('div').hide();
    $(this).find('div').show();
});
