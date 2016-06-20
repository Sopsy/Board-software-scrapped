import YQuery from '../YQuery';
import YBoard from '../YBoard';
import Toast from '../Toast';
import Captcha from '../Captcha';

class PostForm
{
    constructor()
    {
        let that = this;
        this.elm = document.getElementById('post-form');
        if (this.elm === null) {
            return;
        }

        this.captcha = null;
        this.locationParent = this.elm.parentNode;
        this.location = this.elm.nextElementSibling;
        this.msgElm = this.elm.querySelector('#post-message');
        this.fileUploadInProgress = false;
        this.fileUploadXhr = null;
        this.submitAfterFileUpload = false;
        this.submitInProgress = false;
        this.skipUnloadWarning = false;
        this.origDestName = false;
        this.origDestValue = false;

        // BBCode buttons
        this.elm.querySelectorAll('.bb-code').forEach(function(elm)
        {
            elm.addEventListener('click', function(e)
            {
                that.insertBbCode(e.target.dataset.code);
            });
        });
        this.elm.querySelector('.e-postform-color-bar').addEventListener('click', function()
        {
            that.toggleBbColorBar();
        });

        // Create thread
        document.querySelectorAll('.create-thread').forEach(function(elm)
        {
            elm.addEventListener('click', function()
            {
                that.show();
            });
        });

        // Reply to a thread
        document.querySelectorAll('.e-thread-reply').forEach(function(elm)
        {
            elm.addEventListener('click', function(e)
            {
                e.preventDefault();

                let threadId = null;
                let thread = e.target.closest('.thread');
                if (thread !== null) {
                    threadId = thread.dataset.id;
                }

                that.threadReply(threadId);
            });
        });

        // Cancel post
        this.elm.querySelector('#reset-button').addEventListener('click', function(e)
        {
            if (that.msgElm.value.length === 0 || confirm(messages.confirmPostCancel)) {
                e.preventDefault();
                that.reset(true);
            }
        });

        // Upload file after change
        this.elm.querySelector('#post-files').addEventListener('change', function()
        {
            that.uploadFile();
        });

        // Remove file -button
        this.elm.querySelector('#remove-file').addEventListener('click', function()
        {
            that.deleteUploadedFile();
            that.removeFile();
        });

        // Toggle post options
        this.elm.querySelector('.toggle-options').addEventListener('click', function(e)
        {
            e.preventDefault();
            document.getElementById('post-options').toggle();
        });

        // Submit a post
        this.elm.addEventListener('submit', function(e)
        {
            that.submit(e);
        });

        this.elm.querySelectorAll('input, select, textarea').forEach(function(elm)
        {
            elm.addEventListener('focus', function(e)
            {
                that.renderCaptcha();
            });
        });

        // Confirm page leave if there's text in the form
        window.addEventListener('beforeunload', function(e)
        {
            if (!that.skipUnloadWarning && that.msgElm !== null && that.msgElm.value.length !== 0) {
                e.returnValue = messages.confirmPageLeave;
            }
        });
    }

    bindPostEvents(elm)
    {
        let that = this;

        // Reply to a post
        elm.querySelectorAll('.e-post-reply').forEach(function(elm)
        {
            elm.addEventListener('click', function(e)
            {
                e.preventDefault();
                that.postReply(e.target.closest('.post').dataset.id);
                that.msgElm.focus();
            });
        });
    }

    show(isReply)
    {
        if (!isReply) {
            // Reset if we click the "Create thread" -button
            this.reset();
        }

        this.renderCaptcha();

        this.elm.classList.add('visible');
        if (this.msgElm.offsetParent !== null) {
            this.msgElm.focus();
        }
    }

    renderCaptcha()
    {
        let that = this;
        if (this.captcha !== null || !Captcha.isEnabled()) {
            return;
        }

        this.captcha = new Captcha(this.elm.querySelector('.captcha'), {
            'size': 'invisible',
            'callback': function(response)
            {
                that.submit(null, response);
            },
        });
    }

    hide()
    {
        this.elm.classList.remove('visible');
    }

    reset(resetForm = false)
    {
        if (resetForm) {
            this.elm.reset();
        }

        if (this.location !== null) {
            this.locationParent.insertBefore(this.elm, this.location);
        } else {
            this.locationParent.appendChild(this.elm);
        }

        this.removeFile();
        this.resetDestination();
        this.hide();
    }

    focus()
    {
        this.msgElm.focus();
    }

    setDestination(isReply, destination)
    {
        this.saveDestination();
        let name = 'board';

        if (isReply) {
            name = 'thread';
        }

        let postDestination = this.elm.querySelector('#post-destination');
        postDestination.setAttribute('name', name);
        postDestination.value = destination;
    }

    saveDestination()
    {
        let destElm = this.elm.querySelector('#post-destination');
        let boardSelector = this.elm.querySelector('#label-board');

        // Hide board selector
        if (boardSelector !== null) {
            boardSelector.hide();
            boardSelector.querySelector('select').required = false;
            return true;
        }

        if (this.origDestName) {
            return true;
        }

        this.origDestName = destElm.getAttribute('name');
        this.origDestValue = destElm.value;

        return true;
    }

    resetDestination()
    {
        let destElm = this.elm.querySelector('#post-destination');
        let boardSelector = this.elm.querySelector('#label-board');

        // Restore board selector
        if (boardSelector !== null) {
            boardSelector.show('flex');
            boardSelector.querySelector('select').required = true;
        }

        if (!this.origDestName) {
            return true;
        }

        destElm.setAttribute('name', this.origDestName);
        destElm.value = this.origDestValue;

        this.origDestName = false;
        this.origDestValue = false;

        return true;
    }

    insertBbCode(code)
    {
        this.msgElm.insertAtCaret('[' + code + ']', '[/' + code + ']');
    }

    toggleBbColorBar()
    {
        this.elm.querySelector('#color-buttons').toggle();
        this.msgElm.focus();
    }

    uploadFile()
    {
        this.elm.querySelector('#remove-file').show();

        // Abort any ongoing uploads
        this.deleteUploadedFile();
        this.removeFile(true);

        let fileInput = this.elm.querySelector('#post-files');
        let fileNameElm = this.elm.querySelector('#file-name');

        fileNameElm.value = '';
        this.submitAfterFileUpload = false;

        this.updateFileProgressBar(1);

        // Calculate upload size and check it does not exceed the set maximum
        let maxSize = fileInput.dataset.maxsize;
        let fileList = fileInput.files;
        let fileSizeSum = 0;
        for (let i = 0, file; file = fileList[i]; i++) {
            fileSizeSum += file.size;
        }

        if (fileSizeSum > maxSize) {
            Toast.warning(messages.maxSizeExceeded);
            this.updateFileProgressBar(0);
            return false;
        }

        let fd = new FormData();
        Array.from(fileList).forEach(file =>
        {
            fd.append('files[]', file);
        });

        this.fileUploadInProgress = true;

        let fileName = fileInput.value.split('\\').pop().split('.');
        fileName.pop();
        fileNameElm.value = fileName.join('.');

        let that = this;
        let fileUpload = YQuery.post('/api/file/create', fd, {
            'timeout': 0,
            'contentType': null,
            'xhr': function(xhr)
            {
                if (!xhr.upload) {
                    return xhr;
                }

                xhr.upload.addEventListener('progress', function(e)
                {
                    if (e.lengthComputable) {
                        let percent = Math.round((e.loaded / e.total) * 100);
                        if (percent < 0) {
                            percent = 0;
                        } else {
                            if (percent > 99) {
                                percent = 99;
                            }
                        }
                        that.updateFileProgressBar(percent);
                    }
                });

                return xhr;
            },
        }).onLoad(function(xhr)
        {
            that.fileUploadInProgress = false;
            that.updateFileProgressBar(100);
            let data = JSON.parse(xhr.responseText);
            if (data.message.length !== 0) {
                that.elm.querySelector('#file-id').value = data.message[0];

                if (that.submitAfterFileUpload) {
                    that.submit();
                }
            } else {
                Toast.error(messages.errorOccurred);
                that.removeFile();
                that.updateFileProgressBar(0);
            }
        }).onError(function()
        {
            that.removeFile();
            that.updateFileProgressBar(0);
        });

        this.fileUploadXhr = fileUpload.getXhrObject();
    }

    removeFile(refresh = false)
    {
        if (this.fileUploadXhr !== null) {
            this.fileUploadXhr.abort();
            this.fileUploadXhr = null;
        }

        if (!refresh) {
            this.elm.querySelector('#remove-file').hide();
            this.elm.querySelector('#post-files').value = '';
        }

        this.elm.querySelector('#file-id').value = '';
        this.elm.querySelector('#file-name').value = '';
        this.updateFileProgressBar(0);
        this.elm.querySelector('input[type="submit"].button').disabled = false;
        this.fileUploadInProgress = false;
        this.submitAfterFileUpload = false;
    }

    deleteUploadedFile()
    {
        let fileIdElm = this.elm.querySelector('#file-id');
        if (fileIdElm.value !== '') {
            YQuery.post('/api/file/delete', {
                'fileId': fileIdElm.value,
            });
        }
    }

    updateFileProgressBar(progress)
    {
        if (progress < 0) {
            progress = 0;
        } else {
            if (progress > 100) {
                progress = 100;
            }
        }

        let elm = this.elm.querySelector('.file-progress');
        let bar = elm.querySelector('div');

        if (progress === 0) {
            bar.style.width = 0;
            elm.classList.remove('in-progress');
        } else {
            bar.style.width = progress + '%';
            elm.classList.add('in-progress');
        }
    }

    threadReply(threadId)
    {
        if (threadId !== null) {
            YBoard.Thread.getElm(threadId).appendChild(this.elm);
            this.show(true);
            this.setDestination(true, threadId);
        }

        this.msgElm.focus();
    }

    postReply(postId)
    {
        let selectedText = YBoard.getSelectionText();

        YBoard.Post.getElm(postId).appendChild(this.elm);
        this.show(true);

        this.setDestination(true, this.elm.closest('.thread').dataset.id);

        this.msgElm.focus();
        let append = '';
        if (this.msgElm.value.substr(-1) !== '\n' && this.msgElm.value.length !== 0) {
            if (this.msgElm.value.substr(-1) !== '\n') {
                append += '\n\n';
            } else {
                append += '\n';
            }
        }
        append += '>>' + postId + '\n';

        // If any text on the page was selected, add it to post form with quotes
        if (selectedText !== '') {
            append += '>' + selectedText.replace(/(\r\n|\n|\r)/g, '$1>') + '\n';
        }

        this.msgElm.insertAtCaret(append);
    }

    submit(e = null, captchaResponse = null)
    {
        let that = this;
        if (typeof e === 'object' && e !== null) {
            e.preventDefault();
        }

        // Invoke the reCAPTCHA if the response is null (= not completed)
        if (this.captcha !== null && captchaResponse === null) {
            console.log(2);
            this.captcha.execute();

            return;
        }

        let submitButton = this.elm.querySelector('input[type="submit"].button');

        // File upload in progress -> wait until done
        if (this.fileUploadInProgress) {
            Toast.info(messages.waitingForFileUpload);
            submitButton.disabled = true;
            this.submitAfterFileUpload = true;
            return false;
        }

        // Prevent duplicate submissions by double clicking etc.
        if (this.submitInProgress) {
            return false;
        }
        this.submitInProgress = true;

        this.elm.querySelector('#post-files').value = '';

        let fd = new FormData(this.elm);
        fd.append('captchaResponse', captchaResponse);

        YQuery.post('/api/post/create', fd, {
            'contentType': null,
        }).onLoad(function(xhr)
        {
            let dest = document.getElementById('post-destination');
            let thread;
            if (dest.getAttribute('name') !== 'thread') {
                thread = null;
            } else {
                thread = dest.value;
            }

            if (thread !== null) {
                YBoard.Thread.AutoUpdate.runOnce(thread);
                // Reset post form
                that.reset(true);
            } else {
                if (xhr.responseText.length === 0) {
                    that.skipUnloadWarning = true;
                    YBoard.pageReload();
                } else {
                    let data = JSON.parse(xhr.responseText);
                    if (typeof data.message === 'undefined') {
                        Toast.error(messages.errorOccurred);
                    } else {
                        that.skipUnloadWarning = true;
                        window.location = '/' + fd.get('board') + '/' + data.message;
                    }
                }
            }
        }).onEnd(function()
        {
            submitButton.disabled = false;
            that.submitInProgress = false;

            if (that.captcha !== null) {
                that.captcha.reset();
            }
        });
    }
}

export default PostForm;
