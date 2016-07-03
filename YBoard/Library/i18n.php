<?php
namespace YBoard\Library;

class i18n
{
    protected $localeFilesPath;

    public function __construct($localeFilesPath)
    {
        if (!is_dir($localeFilesPath)) {
            throw new \Exception('Invalid path for locale files: ' . $localeFilesPath);
        }
        $this->localeFilesPath = $localeFilesPath;
    }

    public function loadLocale($locale, $domain = 'default')
    {
        $locale = addslashes($locale);
        $domain = addslashes($domain);

        // Load localization
        setlocale(LC_ALL, $locale);
        bindtextdomain($domain, $this->localeFilesPath);
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);
    }

    public function getPreferredLocale()
    {
        // Originally from http://www.thefutureoftheweb.com/blog/use-accept-language-header
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return false;
        }

        $locales = [];
        // break up string into pieces (languages and q factors)
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'],
            $parseLocale);

        if (count($parseLocale[1])) {
            // create a list like "en" => 0.8
            $locales = array_combine($parseLocale[1], $parseLocale[4]);

            // set default to 1 for any without q factor
            foreach ($locales as $locale => $val) {
                if ($val === '') {
                    $locales[$locale] = 1;
                }
            }

            // sort list based on value
            arsort($locales, SORT_NUMERIC);
        }

        $tmpArray = [];
        foreach ($locales AS $locale => $priority) {
            if (strpos($locale, '-')) {
                $locale = explode('-', $locale);
                $locale[1] = mb_strtoupper($locale[1]);
                $tmpArray[implode('_', $locale)] = $priority;
            } else {
                $tmpArray[$locale] = $priority;
            }
        }
        $locales = $tmpArray;
        array_unique($locales);

        foreach ($locales AS $locale => $priority) {
            $arr = glob($this->localeFilesPath . '/' . $locale . '*');
            if (!empty($arr[0])) {
                $arr = array_reverse(explode('/', $arr[0]));
                $locale = $arr[0];

                return $locale;
            }
        }

        return false;
    }
}
