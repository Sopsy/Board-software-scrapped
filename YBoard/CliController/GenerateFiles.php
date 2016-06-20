<?php
namespace YBoard\CliController;

use YFW\Library\i18n;

class GenerateFiles
{
    protected $config;

    public function index(): void
    {
        $this->jsLocales();
        $this->jsConfig();
    }

    public function jsLocales(): void
    {
        $i18n = new i18n(ROOT_PATH . '/YBoard/Locale');

        $outPath = ROOT_PATH . '/static/js/locale';
        if (!is_dir($outPath)) {
            mkdir($outPath, 2774, true);
        }

        foreach ($i18n->listLocales() as $locale => $domains) {
            foreach ($domains as $domain) {
                $i18n->loadLocale($locale, $domain);

                $outFile = $outPath . '/' . $locale . '.' . $domain . '.js';
                file_put_contents($outFile, $this->getJsMessages());
                echo 'Generated: ' . $locale . '.' . $domain . "\n";
            }
        }
    }

    public function jsConfig(): void
    {
        // Load config
        $this->config = require(ROOT_PATH . '/YBoard/Config/App.php');

        $outPath = ROOT_PATH . '/static/js';
        if (!is_dir($outPath)) {
            mkdir($outPath, 0774, true);
        }

        file_put_contents($outPath . '/config.js', $this->getJsConfig());
        echo "JS config file generated\n";
    }

    protected function getJsMessages(): string
    {
        $messages = [
            'loading' => _('Loading...'),
            'undo' => _('Undo'),
            'errorOccurred' => _('An error occurred'),
            'timeoutWarning' => _('Loading timed out – please check your internet connection'),
            'networkError' => _('Network error – please check that you are connected to the internet'),
            'logOutConfirm' => _('Log out?'),
            'signUp' => _('Sign up'),
            'cancel' => _('Cancel'),
            'logIn' => _('Log_in'),
            'notifications' => _('Notifications'),
            'maxSizeExceeded' => _('Your files exceed the maximum upload size.'),
            'confirmDeletePost' => _('Delete post?'),
            'confirmDeleteFile' => _('Delete file?'),
            'confirmPostCancel' => _('Cancel your post?'),
            'replies' => _('Replies'),
            'op' => _('OP'),
            'you' => _('You'),
            'postSent' => _('Post sent'),
            'postDeleted' => _('Post deleted'),
            'fileDeleted' => _('File deleted'),
            'postReported' => _('Post reported'),
            'threadHidden' => _('Thread hidden'),
            'threadRestored' => _('Thread restored'),
            'threadLocked' => _('Thread locked'),
            'threadUnlocked' => _('Thread unlocked'),
            'threadStickied' => _('Thread stickied'),
            'threadUnstickied' => _('Thread unstickied'),
            'threadDeleted' => _('This thread has been deleted'),
            'reportCleared' => _('Report cleared'),
            'banAdded' => _('Ban added'),
            'confirmUnload' => _('Your message will be lost.'),
            'noNewReplies' => _('No new replies'),
            'showFullMessage' => _('show full message'),
            'passwordsDoNotMatch' => _('The two passwords do not match'),
            'passwordChanged' => _('Password changed'),
            'waitingForFileUpload' => _('Your message will be sent after the file upload is completed.'),
            'confirmPageLeave' => _('Your message might disappear if you leave this page.'),
            'oldBrowserWarning' => _('You are using an outdated browser which does not support some modern techniques used by this website. Please upgrade your browser.'),
        ];

        return 'let messages=' . json_encode($messages);
    }

    protected function getJsConfig(): string
    {
        $config = [
            'staticUrl' => $this->config['url']['static'],
        ];

        if ($this->config['captcha']['enabled']) {
            $config['reCaptchaPublicKey'] = $this->config['captcha']['publicKey'];
        }

        return 'let config=' . json_encode($config);
    }
}
