import YQuery from '../../YQuery';
import YBoard from '../../YBoard';
import Toast from '../../Toast';

class File
{
    constructor(Post)
    {
        this.Post = Post;
    }

    bindEvents(parent)
    {
        let that = this;

        parent.querySelectorAll('.thumbnail .image').forEach(function(elm)
        {
            elm.addEventListener('click', function(e) {
                that.expand(e, that);
            });
        });

        parent.querySelectorAll('.thumbnail .media').forEach(function(elm)
        {
            elm.addEventListener('click', function(e) {
                that.playMedia(e, that);
            });
        });

        parent.querySelectorAll('.e-stop-media').forEach(function(elm)
        {
            elm.addEventListener('click', that.stopAllMedia);
        });
    }

    delete(id)
    {
        if (!confirm(messages.confirmDeleteFile)) {
            return false;
        }

        YQuery.post('/api/post/deletefile', {
            'post_id': id,
            'loadFunction': function()
            {
                this.getElm(id).find('figure').remove();
                Toast.success(messages.fileDeleted);
            },
        });
    }

    expand(e, that)
    {
        function changeSrc(img, src)
        {
            let eolFn = expandOnLoad;
            function expandOnLoad(e)
            {
                e.target.removeEventListener('load', eolFn);
                delete e.target.dataset.expanding;
                clearTimeout(e.target.loading);
                let overlay = e.target.parentNode.querySelector('div.overlay');
                if (overlay !== null) {
                    requestAnimationFrame(function()
                    {
                        overlay.remove();
                    });
                }
            }

            img.dataset.expanding = true;
            img.loading = setTimeout(function()
            {
                let overlay = document.createElement('div');
                overlay.classList.add('overlay', 'center');
                overlay.innerHTML = YBoard.spinnerHtml();
                img.parentNode.appendChild(overlay);
            }, 200);

            img.addEventListener('load', eolFn);
            img.setAttribute('src', src);
        }

        e.preventDefault();
        if (typeof e.target.dataset.expanded === 'undefined') {
            // Expand
            e.target.dataset.expanded = e.target.getAttribute('src');
            requestAnimationFrame(function()
            {
                changeSrc(e.target, e.target.parentNode.getAttribute('href'));
                e.target.closest('.post-file').classList.remove('thumbnail');
            });
        } else {
            // Restore thumbnail
            requestAnimationFrame(function()
            {
                changeSrc(e.target, e.target.dataset.expanded);
                e.target.closest('.post-file').classList.add('thumbnail');
                delete e.target.dataset.expanded;

                // Scroll to top of image
                let elmTop = e.target.getBoundingClientRect().top + window.scrollY;
                if (elmTop < window.scrollY) {
                    window.scrollTo(0, elmTop);
                }
            });
        }
    }

    playMedia(e, that)
    {
        e.preventDefault();

        that.stopAllMedia();

        let fileId = e.target.closest('figure').dataset.id;

        if (typeof e.target.dataset.loading !== 'undefined') {
            return false;
        }

        e.target.dataset.loading = true;

        let loading = setTimeout(function()
        {
            let overlay = document.createElement('div');
            overlay.classList.add('overlay', 'bottom', 'left');
            overlay.innerHTML = YBoard.spinnerHtml();

            requestAnimationFrame(function()
            {
                e.target.appendChild(overlay);
            });
        }, 200);

        YQuery.post('/api/file/getmediaplayer', {'fileId': fileId}).onLoad(function(xhr)
        {
            let figure = e.target.closest('.post-file');
            let message = e.target.closest('.message');

            let data = document.createElement('div');
            data.innerHTML = xhr.responseText;

            // Bind events etc.
            YBoard.initElement(data);

            requestAnimationFrame(function()
            {
                figure.classList.remove('thumbnail');
                figure.classList.add('media-player-container');
                figure.insertBefore(data, figure.firstElementChild);

                // Video volume save/restore
                let video = figure.querySelector('video');
                if (video !== null) {
                    video.addEventListener('volumechange', function(e)
                    {
                        localStorage.setItem('videoVolume', e.target.volume);
                    });

                    let volume = localStorage.getItem('videoVolume');
                    if (volume !== null) {
                        video.volume = volume;
                    }
                }
            });
        }).onEnd(function()
        {
            clearTimeout(loading);
            e.target.querySelectorAll('div.overlay').forEach(function(elm) {
                requestAnimationFrame(function()
                {
                    elm.remove();
                });
            });
            delete e.target.dataset.loading;
        });
    }

    stopAllMedia()
    {
        document.querySelectorAll('.media-player-container').forEach(function(elm) {
            let video = elm.querySelector('video');
            video.pause();
            video.remove();

            requestAnimationFrame(function()
            {
                elm.classList.remove('media-player-container');
                elm.classList.add('thumbnail');
            });
        });
    }
}

export default File;
