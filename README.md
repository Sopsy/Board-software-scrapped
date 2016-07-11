# YBoard
The once to be board software for Ylilauta. Might not always be open source.

Code style is PSR-1/PSR-2, please follow that.

## Software required
* Linux/Unix server
* Nginx
* PHP 7+
* MySQL 5.6+ / MariaDB
* ImageMagick
* FFmpeg 3+
* More to come

Why not Windows? PHP message queue only works on *NIX. That's why. Replace with your own stuff if you want it to run on Windows.
Some path are also hardcoded and may not work with Windows, like "nice", "convert" and "ffmpeg".

For Nginx you should just forward all requests (or just for files that do not exist) to public/index.php.

You also need to setup a different domain for static content.
Do not run any PHP code under that domain, just plain Nginx.
Static root is static/.

Should work with Apache, but it's not supported as we do not use that.

## Cronjobs
To avoid unnecessary load while opening pages, all not-so-important things are run on background with cron.
rundaemon.sh checks once a minute that all message listener processes are running and starts them if required.

You should add the following lines to crontab. Change times as needed.
* 13 * * * * php <ROOT_PATH>/RunCommand.php CronHourly
* 28 1 * * * php <ROOT_PATH>/RunCommand.php CronDaily
* \* * * * * sh <ROOT_PATH>/rundaemon.sh

## Other
* You need to generate locales to the server in order for i18n translations to work.
They use native gettext for maximum performance.
* For discussions in finnish, please see http://ylilauta.org/ohjelmistot/

Sorry for lacking comments.
