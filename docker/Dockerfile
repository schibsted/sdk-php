FROM centos:7

# persistent / runtime deps
RUN yum update -y && yum install -y epel-release && rpm -Uhv http://rpms.famillecollet.com/enterprise/remi-release-7.rpm && yum clean all
RUN yum update -y && yum install -y ca-certificates curl && yum clean all
RUN curl "http://download.opensuse.org/repositories/isv:/ownCloud:/devel/CentOS_7/isv:ownCloud:devel.repo" >/etc/yum.repos.d/isv:ownCloud:devel.repo && yum install -y --nogpgcheck libcap-dummy
RUN yum update -y && yum install -y httpd php56 php56-php-cli php56-php-gd php56-php-intl php56-php-pecl-jsond php56-php-pecl-jsonc php56-php-mcrypt php56-php-pecl-mongo php56-php-mysqlnd php56-php-pecl-sqlite php56-php-soap php56-php-pecl-xdebug php56-php-pecl-xhprof php56-xhprof php56-php-mbstring php56-php-pecl-apcu php56-php-pecl-redis && yum clean all
RUN yum update -y && yum install -y mongodb mysql && yum clean all

RUN mv /etc/httpd/conf/httpd.conf /etc/httpd/conf/httpd.conf.dist && rm -f /etc/httpd/conf.d/* /etc/httpd/sites-enabled/*
COPY docker/config/apache2.conf /etc/httpd/conf/httpd.conf
COPY docker/config/conf.d/modules.load /etc/httpd/conf.d/modules.load
COPY docker/config/conf.d/*.conf /etc/httpd/conf.d/
COPY docker/config/php/php-xdebug.ini /opt/remi/php56/root/etc/php.d/xdebug.ini
COPY docker/config/php/spid.ini /opt/remi/php56/root/etc/php.d/spid.ini
COPY docker/config/sites-enabled/sdk.dev.conf /etc/httpd/sites-enabled/sdk.dev.conf

COPY docker/apache2-foreground /usr/local/bin/
WORKDIR /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
