import YQuery from '../../YQuery';

class Hide
{
    constructor()
    {
        let that = this;

        document.querySelectorAll('.e-thread-hide').forEach(function(elm)
        {
            elm.addEventListener('click', that.toggle);
        });
    }

    toggle(e)
    {
        let thread = e.target.closest('.thread');
        let button = e.currentTarget;

        let create = true;
        if (e.currentTarget.classList.contains('act')) {
            create = false;
        }
        thread.classList.toggle('hidden');

        toggleButton(button);

        YQuery.post(create ? '/api/user/thread/hide/create' : '/api/user/thread/hide/delete',
            {'threadId': thread.dataset.id}).onError(function(xhr)
        {
            thread.classList.toggle('hidden');
            toggleButton(button);
        });

        function toggleButton(elm)
        {
            if (!elm.classList.contains('act')) {
                elm.classList.add('icon-eye', 'act');
                elm.classList.remove('icon-eye-crossed');
            } else {
                elm.classList.add('icon-eye-crossed');
                elm.classList.remove('icon-eye', 'act');
            }
        }
    }
}

export default Hide;
