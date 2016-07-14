<?php
namespace YBoard\Library;

class GeoIP
{
    public static function getCountryCode(string $ip)
    {
        require_once(__DIR__ . '/../Vendor/ip2location.php');
        if (!static::isIpv6($ip)) {
            // IPv4
            $ip2location = new \IP2Location\Database(__DIR__ . '/../Vendor/IP2LOCATION-LITE-DB1.BIN');
        } else {
            // IPv6
            $ip2location = new \IP2Location\Database(__DIR__ . '/../Vendor/IP2LOCATION-LITE-DB1.IPV6.BIN');
        }
        $countryCode = $ip2location->lookup($_SERVER['REMOTE_ADDR'], \IP2Location\Database::COUNTRY)['countryCode'];

        return strtoupper($countryCode);
    }

    protected static function isIpv6(string $ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }
}
