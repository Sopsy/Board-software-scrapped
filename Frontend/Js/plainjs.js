
/*
 // Mobile menu
 // -------------------------------------------

 // Session management
 // -------------------------------------------
 .on('submit', 'form.sessiondestroy', function (event) {
 var sessionId = $(event.target).find('input').val();
 document.getElementById(sessionId).remove();
 })

 // Notifications
 // -------------------------------------------
 .on('click', '.open-notifications', function () {
 YB.notifications.get();
 })

 // Post files
 // -------------------------------------------
 .on('click', '.post figure.thumbnail .image', function (event) {
 if (event.ctrlKey || event.altKey) {
 return true;
 }
 event.preventDefault();
 YB.post.file.expand($(this).parent('figure'));
 })
 .on('click', '.post figure:not(.thumbnail) .image', function (event) {
 if (event.ctrlKey || event.altKey) {
 return true;
 }
 event.preventDefault();
 YB.post.file.restoreThumbnail($(this).parent('figure'));
 })

 .on('click', '.post figure.thumbnail .media', function (event) {
 if (event.ctrlKey || event.altKey) {
 return true;
 }
 event.preventDefault();
 YB.post.file.playMedia($(this).parent('figure'));
 })
 .on('click', '.stop-media', function (event) {
 event.preventDefault();
 YB.post.file.stopAllMedia();
 })
 */


// Post moderation

$('.mod-menu').tooltipster({
    content: YB.spinnerHtml(),
    side: 'bottom',
    animationDuration: 0,
    updateAnimation: null,
    delay: 0,
    arrow: false,
    contentAsHTML: true,
    zIndex: 1001,
    trigger: 'click',
    interactive: 'true',
    functionInit: function (instance, helper) {
        var content = $(helper.origin).next('.mod-menu-html').show().detach();
        instance.content(content);
    }
});

function addBan(e) {
    e.preventDefault();

    if (!('FormData' in window)) {
        toastr.error(messages.oldBrowserWarning, messages.errorOccurred);
        return false;
    }

    var fd = new FormData(e.target);

    var oldHtml = $(e.target).html();
    $(e.target).html(YB.spinnerHtml());

    $.ajax({
        url: e.target.getAttribute('target'),
        type: "POST",
        processData: false,
        contentType: false,
        data: fd
    }).done(function () {
        toastr.success(messages.banAdded);
        closeModals();
    }).fail(function () {
        $(e.target).html(oldHtml);
    });
}


// Start/stop autoupdate

if($('body').hasClass('thread-page')) {
    $(window)
        .on('scroll', function () {
            var windowBottom = $(window).height() + $(window).scrollTop();
            var repliesBottom = $('.replies').offset().top + $('.replies').height();
            if (windowBottom > repliesBottom) {
                if (!$('#post-message').is(':focus')) {
                    YB.thread.ajaxUpdate.start();
                }
            } else {
                YB.thread.ajaxUpdate.stop();
            }
        })
        .on('focus', function () {
            YB.thread.ajaxUpdate.reset();
        });
    // Stop when post form is focused
    $('body')
        .on('focus', '#post-message', function () {
            YB.thread.ajaxUpdate.stop();
        })
        .on('blur', '#post-message', function () {
            YB.thread.ajaxUpdate.start();
        });
}


// -------------------------------------------
// Spoilers & reflinks
// -------------------------------------------
.on('touchstart', '.spoiler:not(.spoiled)', function (e) {
    e.preventDefault();
    e.target.addClass('spoiled');
})
.on('click', false, function (e) {
    document.querySelectorAll('.spoiler.spoiled').forEach(function(elm) {
        elm.removeClass('spoiled');
    });
})
.on('contextmenu', '.reflink', function (e) {
    e.preventDefault();
})

.on('touchstart mouseenter', '.reflink:not(.tooltipstered)', function (e) {
    var elm = $(this);
    reflinkCreateTimeout = setTimeout(function () {
        e.preventDefault();
        var id = elm.data('id');
        var content = YB.spinnerHtml();
        if (YB.post.getElm(id) != null) {
            content = YB.post.getElm(id).innerHTML;
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
                $.post('/scripts/posts/get', {'postId': id}).done(function (data) {
                    // Update timestamps
                    data = $(data);
                    data.find('.datetime').localizeTimestamp(this);

                    instance.content(data);
                }).fail(function () {
                    instance.close();
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
    if (YB.post.getElm(id) != null) {
        e.preventDefault();
        window.location = window.location.href.split('#')[0] + '#post-' + id;
    }

});
