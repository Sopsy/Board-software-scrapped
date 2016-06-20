<?php
namespace YFW\Library;

class Text
{
    public static function truncate(string $str, int $length): string
    {
        $curlength = mb_strlen($str);

        if ($curlength <= $length) {
            return $str;
        }

        $str = mb_substr($str, 0, $length - 3);
        $str .= '...';

        return $str;
    }

    public static function countNewlines(string $str): int
    {
        return substr_count($str, "\n");
    }

    public static function limitEmptyLines(string $str, int $maxLines = 1): string
    {
        $str = str_replace("\r", '', $str);

        return preg_replace('/(\n){' . ($maxLines + 1) . ',}/', str_repeat("\n", $maxLines + 1), $str);
    }

    public static function removeForbiddenUnicode(string $text): string
    {
        // Remove invisible characters and characters that mess up with the formatting.
        $unicode = [
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', // Unicode control characters
            '/[\x{0080}-\x{009F}]/u',             // Unicode control characters, disallowed in HTML
            '/[\x{00A0}]/u',                      // 'NO-BREAK SPACE' (U+00A0)
            '/[\x{00AD}]/u',                      // 'SOFT HYPHEN' (U+00AD)
            '/[\x{034F}]/u',                      // 'COMBINING GRAPHEME JOINER' (U+034F)
            '/[\x{115F}]/u',                      // 'HANGUL CHOSEONG FILLER' (U+115F)
            '/[\x{2000}-\x{200F}]/u',             // Spaces and LTR + RTL marks (U+2000 - U+200F)
            '/[\x{2028}]/u',                      // 'LINE SEPARATOR' (U+2028)
            '/[\x{2028}]/u',                      // 'PARAGRAPH SEPARATOR' (U+2029)
            '/[\x{202A}-\x{202F}]/u',             // LTR + RTL embedding and overrides (U+202A - U+202F)
            '/[\x{205F}-\x{2064}]/u',             // Invisible math chars (U+205F - U+2064)
            '/[\x{206A}-\x{206F}]/u',             // 'INHIBIT SYMMETRIC SWAPPING' etc. (U+206A - U+206F)
            '/[\x{2800}]/u',                      // 'BRAILLE PATTERN BLANK' (U+2800)
            '/[\x{3000}]/u',                      // 'IDEOGRAPHIC SPACE' (U+3000)
            '/[\x{FE00}-\x{FE0F}]/u',             // Variation selectors (U+FE00 - U+FE0F)
            '/[\x{FEFF}]/u',                      // 'ZERO WIDTH NO-BREAK SPACE' (U+FEFF)
            '/[\x{FFFE}-\x{FFFF}]/u',             // Invalid unicode (U+FFFE - U+FFFF)
        ];
        $text = preg_replace($unicode, ' ', $text);

        return $text;
    }

    public static function strToUrlSafe(string $str): string
    {
        $urlSafeStr = mb_strtolower($str);
        $urlSafeStr = str_ireplace('ä', 'a', $urlSafeStr);
        $urlSafeStr = str_ireplace('å', 'a', $urlSafeStr);
        $urlSafeStr = str_ireplace('ö', 'o', $urlSafeStr);
        $urlSafeStr = str_replace(' ', '-', $urlSafeStr);
        $urlSafeStr = preg_replace('#[^a-z0-9\-\_]#', '-', $urlSafeStr);
        $urlSafeStr = preg_replace('#-([-]+)#', '-', $urlSafeStr);
        $urlSafeStr = preg_replace('#^([-]+)#', '', $urlSafeStr);
        $urlSafeStr = preg_replace('#([-]+)$#', '', $urlSafeStr);

        return $urlSafeStr;
    }

    public static function randomStr(int $length = 8, bool $uppercase = true, bool $numbers = true): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        if ($uppercase) {
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        if ($numbers) {
            $chars .= '0123456789';
        }

        return substr(str_shuffle($chars), 0, $length);
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes < 1024) {
            return $bytes . ' ' . _('B');
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, $precision) . ' ' . _('KB');
        }

        return round($bytes / 1048576, $precision) . ' ' . _('MB');
    }

    public static function clickableLinks(string $message): string
    {
        if (strpos($message, '://') === false) {
            return $message;
        }

        $message = preg_replace('#(https?://[^\s<>\[\]"]+)#i', '<a href="$1" target="_blank" rel="nofollow">$1</a>',
            $message);

        return $message;
    }

    public static function formatMessage(string $message): string
    {
        $message = htmlspecialchars($message);
        $message = nl2br($message);
        $message = static::clickableLinks($message);
        $message = static::addQuotes($message);
        $message = BbCode::format($message);

        return $message;
    }

    public static function addQuotes(string $message): string
    {
        if (strpos($message, '>') === false && strpos($message, '<') === false) {
            return $message;
        }

        $search = [
            '/(^|[\n\]])(&gt;)(?!&gt;[0-9]+)([^\n]+)/is',
            '/(^|[\n\]])(&lt;)([^\n]+)/is',
        ];
        $replace = [
            '$1<span class="quote">$2$3</span>',
            '$1<span class="quote blue">$2$3</span>',
        ];

        return preg_replace($search, $replace, $message);
    }

    public static function formatUsername($username): string
    {
        if (empty($username)) {
            return _('Anonymous');
        }

        return $username;
    }

    public static function stripFormatting(string $message): string
    {
        $message = preg_replace('/\s\s+/', ' ', str_replace(["\n", "\r"], ' ', $message));
        $message = BbCode::strip($message);
        $message = static::removeForbiddenUnicode($message);
        $message = trim($message);

        return $message;
    }

    public static function filterHex(string $string): string
    {
        return preg_replace('/[^0-9a-f]/', '', strtolower($string));
    }

    public static function formatDuration(int $duration): string
    {
        return floor($duration / 60) . ':' . str_pad($duration % 60, 2, '0', STR_PAD_LEFT);
    }
}
