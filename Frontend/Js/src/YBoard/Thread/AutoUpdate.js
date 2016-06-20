import YBoard from '../../YBoard';
import YQuery from '../../YQuery';
import Toast from '../../Toast';

class AutoUpdate
{
    constructor()
    {
        let that = this;
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

        document.querySelectorAll('.thread .e-get-replies').forEach(function(elm) {
            elm.addEventListener('click', function(e) {
                e.preventDefault();
                that.runOnce(elm.closest('.thread').dataset.id);
            });
        });
    }

    run(manual)
    {
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

        let thread = YBoard.Thread.getElm(this.threadId);
        let replies = thread.querySelectorAll('.reply');

        let fromId = 0;
        if (replies.length !== 0) {
            fromId = replies[replies.length - 1];
            fromId = fromId.getAttribute('id').replace('post-', '');
        }

        let visibleReplies = [];
        replies.forEach(function(elm) {
            visibleReplies.push(elm.dataset.id);
        });

        this.nowLoading = true;
        let that = this;
        YQuery.post('/api/thread/getreplies', {
            'threadId': this.threadId,
            'fromId': fromId,
            'newest': true,
            'xhr': function(xhr) {
                xhr.responseType = 'document';

                return xhr;
            },
            'visibleReplies': visibleReplies,
        }).onLoad(function(xhr)
        {
            // Remove deleted replies
            let deletedReplies = xhr.getResponseHeader('X-Deleted-Replies');
            if (deletedReplies !== null) {
                deletedReplies.split(',').forEach(function(id) {
                    let post = document.getElementById('post-' + id);
                    if (post !== null) {
                        post.remove();
                    }
                });
            }

            // Return if there's no new replies and show a notification if we're running manually
            if (xhr.responseText.length === 0) {
                if (manual) {
                    Toast.info(messages.noNewReplies);
                }

                return;
            }

            let data = document.createElement('div');
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
            data.querySelectorAll('.ref').forEach(function(elm) {
                let referredId = elm.dataset.id;
                let referredPost = document.getElementById('post-' + referredId);
                if (referredPost === null) {
                    return true;
                }

                // Create replies-container if it does not exist
                let repliesElm = referredPost.querySelector('.post-replies');
                if (repliesElm === null) {
                    repliesElm = document.createElement('div');
                    repliesElm.classList.add('post-replies');
                    repliesElm.innerHTML = messages.replies + ':';
                    referredPost.appendChild(repliesElm);
                }

                let clone = elm.cloneNode(true);
                clone.innerHTML = clone.innerHTML.replace(' (' + messages.op + ')', '');
                YBoard.addTooltipEventListener(clone);
                repliesElm.appendChild(document.createTextNode(' '));
                repliesElm.appendChild(clone);
            });

            YBoard.initElement(data);
            requestAnimationFrame(function() {
                thread.querySelector('.replies').appendChild(data);
            });

            // Run again
            if (!manual) {
                that.nextRunTimeout = setTimeout(function()
                {
                    that.start();
                }, that.nextLoadDelay);
            }
        }).onError(function(xhr)
        {
            if (xhr.status === 410) {
                // Thread was deleted
                let thread = document.getElementById('thread-' + that.threadId);
                if (thread !== null) {
                    thread.remove();
                }
            }
            that.stop();
        }).onEnd(function()
        {
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
    }

    runOnce(thread)
    {
        this.threadId = thread;
        this.run(true);
    }

    start()
    {
        this.isActive = true;
        if (this.startDelayTimeout) {
            clearTimeout(this.startDelayTimeout);
        }

        let that = this;
        this.threadId = $('.thread:first').data('id');
        this.startDelayTimeout = setTimeout(function()
        {
            that.run(false);
        }, 1000);

        return true;
    }

    stop()
    {
        if (!this.isActive) {
            return true;
        }

        if (this.startDelayTimeout) {
            clearTimeout(this.startDelayTimeout);
        }
        this.isActive = false;

        this.reset();
        return true;
    }

    restart()
    {
        this.stop();
        this.start();
    }

    reset()
    {
        this.nowLoading = false;
        this.newReplies = 0;
        this.runCount = 0;
        if (document.title !== this.originalDocTitle) {
            document.title = this.originalDocTitle;
        }

        if (this.nextRunTimeout) {
            clearTimeout(this.nextRunTimeout);
        }
    }
}

export default AutoUpdate;
