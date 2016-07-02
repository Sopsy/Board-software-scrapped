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

    console.log(elm.data('open'));
    if (!elm.data('open')) {
        elm.html(messages.cancel);

        $('#loginbutton').attr('name', 'signup').val(messages.signUp);
        $('#signupform').slideDown();
        elm.data('open', true);
    } else {
        elm.html(messages.signUp);

        $('#loginbutton').attr('name', 'login').val(messages.logIn);
        $('#signupform').slideUp();
        $('#signupform input').val('');
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

    var form = $('form#post');
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
        $('#post-progress').find('div').css('width', '');
        form[0].reset();
    }).fail(function (xhr, textStatus, errorThrown) {
        if (xhr.responseText.length != 0) {
            try {
                var text = JSON.parse(xhr.responseText);
                errorThrown = text.message;
            } catch(e) {}
        }
        toastr.error(errorThrown, messages.errorOccurred);
    }).always(function () {
        submitInProgress = false;
    });
}
