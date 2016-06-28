function signupForm(elm, e) {
    e.preventDefault();
    elm = $(elm);

    console.log(elm.data('open'));
    if (!elm.data('open')) {
        elm.html(messages.cancel);

        $('#loginbutton').attr('name', 'signup').val(messages.signUp);
        $('#signupform').slideDown();
        elm.data('open', true);
    } else {
        elm.html(messages.signUp);

        $('#loginbutton').attr('name', 'login').val(messages.logIn);
        $('#signupform').slideUp();
        $('#signupform input').val('');
        elm.data('open', false);
    }
}
