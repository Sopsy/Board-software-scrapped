class Captcha
{
    constructor(elm, options = {})
    {
        if (typeof grecaptcha === 'undefined' || !elm || elm.innerHTML.length !== 0) {
            // Captcha not enabled, grecaptcha -library not loaded, captcha element not found or already rendered
            return;
        }

        options = Object.assign({'sitekey': config.reCaptchaPublicKey}, options);
        this.widgetId = grecaptcha.render(elm, options);

        return;
    }

    static isEnabled()
    {
        return typeof grecaptcha !== 'undefined';
    }

    execute()
    {
        if (typeof grecaptcha === 'undefined') {
            // Captcha not enabled or grecaptcha -library not loaded
            return false;
        }

        grecaptcha.execute(this.widgetId);

        return true;
    }

    reset()
    {
        if (typeof grecaptcha === 'undefined') {
            // Captcha not enabled or grecaptcha -library not loaded
            return false;
        }

        grecaptcha.reset(this.widgetId);

        return true;
    }
}

export default Captcha;
