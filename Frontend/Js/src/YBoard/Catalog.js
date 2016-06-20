class Catalog
{
    constructor()
    {
        let searchInput = document.getElementById('search-catalog');
        if (searchInput) {
            searchInput.addEventListener('keyup', this.search);
        }
    }

    search(e)
    {
        let elm = e.target;
        let word = elm.value;
        let threads = document.querySelectorAll('.thread');

        if (word.length === 0) {
            threads.forEach(function(elm) {
                elm.style.display = '';
            });
        } else {
            threads.hide();
            threads.forEach(function(elm)
            {
                if (elm.querySelector('h3').innerHTML.toLowerCase().indexOf(word.toLowerCase()) !== -1) {
                    elm.show('flex');
                    return true;
                }
                if (elm.querySelector('.message').innerHTML.toLowerCase().indexOf(word.toLowerCase()) !== -1) {
                    elm.show('flex');
                    return true;
                }
            });
        }
    }
}
export default Catalog;
