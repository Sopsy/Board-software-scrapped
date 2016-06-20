#!/usr/bin/env bash
DBNAME=yboard
DBPASSWD=vagrant

# Install PHP7, Nginx, MySQL, PNGCrush, JpegOptim, ImageMagick
apt update
debconf-set-selections <<< "mysql-server mysql-server/root_password password ${DBPASSWD}"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password ${DBPASSWD}"

apt install -y jpegoptim pngcrush imagemagick nginx mysql-server-5.7 php7.1-fpm php7.1-mysql php7.1-mbstring php7.1-gd php-apcu php-xdebug php-imagick ffmpeg

# Nginx config
sed -i -e 's/^user .*;/user ubuntu;/g' /etc/nginx/nginx.conf
sed -i -e 's/#\? \?use .*;//g' /etc/nginx/nginx.conf
sed -i -e 's/#\? \?multi_accept .*;/multi_accept on; use epoll;/g' /etc/nginx/nginx.conf
# Sendfile messes up with caches
sed -i -e 's/\t\?sendfile on;/\tsendfile off;/g' /etc/nginx/nginx.conf

cat > /etc/nginx/conf.d/zz-setit.conf << EOM
# Basic
fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;

client_max_body_size 200M;

# Gzip
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types image/svg+xml text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
gzip_min_length 512;
EOM

cat > /etc/nginx/snippets/php-upstream.conf << EOM
location ~ \.php\$ {
    # This is how YBoard knows it's being developed
    fastcgi_param APPLICATION_ENVIRONMENT development;

    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
}
EOM

# Nginx vhosts
rm /etc/nginx/sites-available/*
rm /etc/nginx/sites-enabled/*
cat > /etc/nginx/sites-available/default << EOM
server {
    server_name _;
    listen 80 default_server;
    listen [::]:80 default_server;
    root /vagrant/public;

    index index.php;
    try_files \$uri /index.php?\$args;

    include snippets/php-upstream.conf;

    location /static/ {
        root /vagrant;
        try_files \$uri =404;

        location ~ \.php\$ { return 404; }
    }
    location /file/ {
        root /vagrant;
        try_files \$uri =404;

        location ~ ^/static/file/([a-z0-9]+)/o/([a-z0-9]+)(\.\w+)\$ { return 404; }
        location ~ \.php\$ { return 404; }

        # Files do not use query strings.
        if (\$query_string != '') { return 404; }

        # Fake filenames for browsers
        rewrite ^/static/file/([a-z0-9]+)/o/([a-z0-9]+)/(.+)(\.\w+)\$ /static/file/\$1/o/\$2\$4 break;
    }

    location /phpmyadmin/ {
        root /usr/share;
        include snippets/php-upstream.conf;
    }
}
EOM
ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# PHP config
#sed -i -e 's/;\?opcache.enable=.*/opcache.enable=1/g' /etc/php/7.1/fpm/php.ini
sed -i -e 's/upload_max_filesize \?= \?.*/upload_max_filesize = 200M/g' /etc/php/7.1/fpm/php.ini
sed -i -e 's/post_max_size \?= \?.*/post_max_size = 200M/g' /etc/php/7.1/fpm/php.ini
sed -i -e 's/error_reporting \?= \?.*/error_reporting = E_ALL/g' /etc/php/7.1/fpm/php.ini
sed -i -e 's/^user \?= \?.*/user = ubuntu/g' /etc/php/7.1/fpm/pool.d/www.conf
sed -i -e 's/^group \?= \?.*/group = ubuntu/g' /etc/php/7.1/fpm/pool.d/www.conf
sed -i -e 's/^listen.owner \?= \?.*/listen.owner = ubuntu/g' /etc/php/7.1/fpm/pool.d/www.conf
sed -i -e 's/^listen.group \?= \?.*/listen.group = ubuntu/g' /etc/php/7.1/fpm/pool.d/www.conf
echo 'xdebug.profiler_enable_trigger=1' >> /etc/php/7.1/fpm/conf.d/20-xdebug.ini
echo 'xdebug.profiler_output_dir=/vagrant/profiler' >> /etc/php/7.1/fpm/conf.d/20-xdebug.ini

# MySQL config
cat > /etc/mysql/mysql.conf.d/zz-setit.cnf << EOM
[mysqld]
skip-name-resolve

# Basics
group_concat_max_len = 65536
innodb_file_per_table = 1
innodb_log_file_size = 128M

# Slowlog
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 0.03 # Yup. 0.03 seconds is slow for a query.
log_queries_not_using_indexes = 1
EOM

# Install PHPMyAdmin
debconf-set-selections <<< "phpmyadmin phpmyadmin/dbconfig-install boolean true"
debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/app-pass string password"
debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/admin-pass password ${DBPASSWD}"
debconf-set-selections <<< "phpmyadmin phpmyadmin/reconfigure-webserver multiselect"
apt install phpmyadmin -y

mysql -uroot -p${DBPASSWD} -e "CREATE DATABASE IF NOT EXISTS ${DBNAME};"
mysql -uroot -p${DBPASSWD} ${DBNAME} < /vagrant/schema.sql

# Set locales
update-locale LANG=en_US.UTF-8 LANGUAGE=en_US.UTF-8 LC_ALL=en_US.UTF-8
sed -i -e 's/# fi_FI.UTF-8 UTF-8/fi_FI.UTF-8 UTF-8/g' /etc/locale.gen
locale-gen

# Restart configured services
service nginx restart
service php7.1-fpm restart
service mysql restart

php /vagrant/RunCommand.php GenerateFiles
