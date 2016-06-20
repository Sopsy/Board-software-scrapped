import YQuery from '../YQuery';
import YBoard from '../YBoard';
import Toast from '../Toast';
import PostFile from './Post/File';

class Post
{
    constructor()
    {
        this.File = new PostFile(this);
    }

    bindEvents(elm)
    {
        let that = this;
        this.File.bindEvents(elm);

        elm.querySelectorAll('.e-post-delete').forEach(function(elm)
        {
            elm.addEventListener('click', that.delete);
        });

        elm.querySelectorAll('.ref').forEach(function(elm)
        {
            elm.addEventListener('click', that.refClick);
        });
    }

    refClick(e)
    {
        let referred = e.currentTarget.dataset.id;
        if (typeof referred === 'undefined') {
            return true;
        }

        if (document.getElementById('post-' + referred) !== null) {
            e.preventDefault();
            document.location.hash = '#post-' + referred;
        }
    }

    getElm(id)
    {
        return document.getElementById('post-' + id);
    }

    delete(e)
    {
        if (!confirm(messages.confirmDeletePost)) {
            return false;
        }

        let post = e.target.closest('.post, .thread');
        let id = post.dataset.id;
        YQuery.post('/api/post/delete', {'postId': id}).onLoad(function()
        {
            post.remove();
            if (YBoard.Thread.getElm(id) !== null) {
                if (document.body.classList.contains('thread-page')) {
                    // We're in the thread we just deleted
                    YBoard.returnToBoard();
                } else {
                    // The deleted post is a thread and not the opened thread
                    YBoard.Thread.getElm(id).remove();
                }
            }
            Toast.success(messages.postDeleted);
        });
    }
}

export default Post;
