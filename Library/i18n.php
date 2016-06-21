<?php

namespace Library;

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
        bindtextdomain($domain, $localeFilesPath);
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);
    }

    public function getPreferredTimezone($ip = false)
    {
        if (!$ip) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (!function_exists('geoip_region_by_name') || !function_exists('geoip_time_zone_by_country_and_region')) {
            trigger_error('Cannot find the required PHP-GeoIP library. Using the default value for timezone.',
                E_USER_NOTICE);

            return $this->config['defaultTimezone'];
        }

        $record = geoip_record_by_name($ip);
        if ($record) {
            return geoip_time_zone_by_country_and_region($record['country_code'], $record['region']);
        } else {
            return $this->config['defaultTimezone'];
        }
    }

    public function getPreferredLocaleFile()
    {
        // Originally from http://www.thefutureoftheweb.com/blog/use-accept-language-header

        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $this->config['defaultLocale'] . '.php';
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
            $arr = glob($this->localesPath . '/' . $locale . '*');
            if (!empty($arr[0])) {
                $arr = array_reverse(explode('/', $arr[0]));
                $locale = $arr[0];

                return $locale;
            }
        }

        return $this->config['defaultLocale'] . '.php';
    }

    public function setDateDefaultTimezone($tz)
    {
        date_default_timezone_set($tz);
    }
}
