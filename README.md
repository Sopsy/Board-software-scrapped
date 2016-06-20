# YBoard
The once to be board software for Ylilauta. Might not always be open source.

Code style is PSR-1/PSR-2, please follow that.

## Software required
* Nginx
* PHP 7+
* MySQL 5.6+ / MariaDB
* More to come

For Nginx you should just forward all requests (or just for files that do not exist) to public/index.php.
Example config coming soon.

You also need to setup a different domain for static content.
Do not run any PHP code under that domain, just plain Nginx.
Static root is static/. Example config coming soon.

Should work with Apache, but it's not supported as we do not use that.

## Other
For discussions in finnish, please see http://ylilauta.org/ohjelmistot/
