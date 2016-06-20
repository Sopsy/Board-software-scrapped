import Modal from '../Modal';
import YBoard from '../YBoard';
import YQuery from '../YQuery';

class Notifications
{
    constructor()
    {
        let that = this;
        this.modal = null;

        document.querySelectorAll('.e-open-notifications').forEach(function(elm) {
            elm.addEventListener('click', function() {
                that.open();
            });
        });
    }

    open()
    {
        let that = this;
        let postXhr = null;

        this.modal = new Modal({
            'title': messages.notifications,
            'content': YBoard.spinnerHtml(),
            'onOpen': function(modal)
            {
                modal.elm.style.willChange = 'contents';
                postXhr = YQuery.post('/api/user/notification/getall', {}, {
                    'errorFunction': null,
                }).onLoad(function(xhr)
                {
                    if (modal.elm === null) {
                        return;
                    }
                    modal.setContent(xhr.responseText);
                    modal.elm.style.willChange = '';
                    that.bindEvents(modal.elm);
                    that.updateUnreadCount(that.getUnreadCount(modal.elm));
                }).onError(function(xhr)
                {
                    if (xhr.responseText.length !== 0) {
                        let json = JSON.parse(xhr.responseText);
                        modal.setContent(json.message);
                    } else {
                        modal.setContent(messages.errorOccurred)
                    }
                });
                postXhr = postXhr.getXhrObject();
            },
            'onClose': function() {
                if (postXhr !== null && postXhr.readyState !== 4) {
                    postXhr.abort();
                }
            },
        });
    }

    bindEvents(elm)
    {
        let that = this;

        // Clicking a link to go to the post
        elm.querySelectorAll('a').forEach(function(elm) {
            elm.addEventListener('click', function(e) {

                // Close modal, just in case we are just highlighting something
                if (that.modal !== null) {
                    if (typeof e.target.tooltip !== 'undefined') {
                        e.target.tooltip.close();
                    }
                    that.modal.close();
                }

                // Mark as read
                if (e.target.closest('.notification').classList.contains('not-read')) {
                    let beaconUrl = '/api/user/notification/markread';
                    let data = new FormData();
                    data.append('id', e.target.closest('.notification').dataset.id);
                    data.append('csrfToken', csrfToken);
                    if ('sendBeacon' in navigator) {
                        // Way faster
                        navigator.sendBeacon(beaconUrl, data);
                    } else {
                        // Fallback for IE ... and SAFARI! Sheesh...
                        e.preventDefault();
                        YQuery.post(beaconUrl, data).onLoad(function()
                        {
                            window.location = e.target.getAttribute('href');
                        });
                    }
                }
            });
        });

        // Marking all notifications as read
        elm.querySelectorAll('.e-mark-all-read').forEach(function(elm) {
            elm.addEventListener('click', function(e) {
                that.markAllRead(e, that);
            });
        });

        // Marking a single notification as read
        elm.querySelectorAll('.e-mark-read').forEach(function(elm) {
            elm.addEventListener('click',  function(e){
                that.markRead(e, that);
            });
        });

        // Everything else
        YBoard.initElement(elm);
    }

    markRead(e, that)
    {
        let notification = e.target.closest('.notification');
        notification.classList.remove('not-read');
        notification.classList.add('is-read');

        YQuery.post('/api/user/notification/markread', {'id': notification.dataset.id}).onError(function()
        {
            notification.classList.add('not-read');
            notification.classList.remove('is-read');
        });

        that.updateUnreadCount(that.getUnreadCount(e.target.closest('.notifications-list')));
    }

    markAllRead(e, that)
    {
        e.target.closest('.modal').querySelectorAll('.notification.not-read').forEach(function(elm) {
            elm.classList.remove('not-read');
            elm.classList.add('is-read');
        });

        YQuery.post('/api/user/notification/markallread');
        that.updateUnreadCount(0);
        that.modal.close();
    }

    getUnreadCount(elm)
    {
        return elm.querySelectorAll('.notification.not-read').length;
    }

    updateUnreadCount(count)
    {
        count = parseInt(count);
        if (count >= 100) {
            count = ':D';
        }

        document.querySelectorAll('.unread-notifications').forEach(function(elm) {
            elm.innerHTML = count;

            if (count === 0) {
                elm.classList.add('none');
            } else {
                elm.classList.remove('none');
            }
        });
    }
}

export default Notifications;
