import YQuery from '../YQuery';
import YBoard from '../YBoard';
import AutoUpdate from './Thread/AutoUpdate';
import Hide from './Thread/Hide';
import Follow from './Thread/Follow';

class Thread {
    constructor() {
        let that = this;
        this.AutoUpdate = new AutoUpdate();
        this.Hide = new Hide();
        this.Follow = new Follow();

        document.querySelectorAll('.replies-buttons').forEach(function(elm) {
            elm.querySelector('.e-more-replies').addEventListener('click', that.expand);
            elm.querySelector('.e-less-replies').addEventListener('click', that.shrink);
        });
    }

    getElm(id)
    {
        return document.getElementById('thread-' + id);
    }

    toggleLock(id)
    {
        if (this.getElm(id).find('h3 a .icon-lock').length == 0) {
            $.post('/scripts/threads/lock', {'threadId': id}).done(function()
            {
                YB.thread.getElm(id).find('h3 a').prepend('<span class="icon-lock icon"></span>');
                toastr.success(messages.threadLocked);
            });
        } else {
            $.post('/scripts/threads/unlock', {'threadId': id}).done(function()
            {
                YB.thread.getElm(id).find('h3 a .icon-lock').remove();
                toastr.success(messages.threadUnlocked);
            });
        }
    }

    toggleSticky(id)
    {
        if (this.getElm(id).find('h3 a .icon-lock').length == 0) {
            $.post('/scripts/threads/stick', {'threadId': id}).done(function()
            {
                YB.thread.getElm(id).find('h3 a').prepend('<span class="icon-pushpin icon"></span>');
                toastr.success(messages.threadStickied);
            });
        } else {
            $.post('/scripts/threads/unstick', {'threadId': id}).done(function()
            {
                YB.thread.getElm(id).find('h3 a .icon-pushpin').remove();
                toastr.success(messages.threadUnstickied);
            });
        }
    }

    shrink(e)
    {
        let thread = e.target.closest('.thread');
        thread.querySelector('.more-replies-container').innerHTML = '';
        thread.querySelector('.e-more-replies').show();
        thread.classList.remove('expanded');
    }

    expand(e)
    {
        // Thread inline expansion
        let thread = e.target.closest('.thread');
        let threadId = thread.dataset.id;
        let fromId = thread.querySelector('.reply')
        if (fromId !== null) {
            fromId = fromId.dataset.id;
        }
        let loadCount = 100;

        YQuery.post('/api/thread/getreplies', {
            'threadId': threadId,
            'fromId': fromId,
            'count': loadCount,
        }).onLoad(function(xhr)
        {
            let data = document.createElement('div');
            data.innerHTML = xhr.responseText;

            let loadedCount = data.querySelectorAll('.post').length;
            if (loadedCount < loadCount) {
                thread.querySelector('.e-more-replies').hide();
            }

            let expandContainer = thread.querySelector('.more-replies-container');
            let firstVisibleReply = expandContainer.querySelector('div');
            if (firstVisibleReply === null) {
                expandContainer.appendChild(data);
            } else {
                expandContainer.insertBefore(data, firstVisibleReply);
            }

            YBoard.initElement(data);

            thread.classList.add('expanded');
        });
    }
}

export default Thread;
