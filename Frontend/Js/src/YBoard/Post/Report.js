import YQuery from '../../YQuery';
import YBoard from '../../YBoard';
import Toast from '../../Toast';
import Modal from '../../Modal';

class Report
{
    openForm()
    {
        Modal.open('/scripts/report/getform', {
            'onAjaxComplete': function()
            {
                YB.Captcha.render('report-captcha');
                document.getElementById('report-post-id').setAttribute('value', id);
            },
        });
    }

    submit(event)
    {
        event.preventDefault();

        let form = $(event.target);
        let fd = new FormData(event.target);

        let oldHtml = $(event.target).html();
        $(event.target).html(YB.spinnerHtml());

        YQuery.post(form.getAttribute('action'), fd).onLoad(function()
        {
            Toast.success(messages.postReported);
            Modal.closeAll();
        }).onError(function(xhr)
        {
            if (xhr.status === 418) {
                YBoard.modal.closeAll();
            } else {
                $(event.target).html(oldHtml);
            }
        });
    }

    setChecked(postId)
    {
        YQuery.post('/scripts/mod/reports/setchecked', {'postId': postId}).done(function()
        {
            Toast.success(messages.reportCleared);
            YBoard.post.getElm(postId).remove();
        });
    }
}

export default Report;
