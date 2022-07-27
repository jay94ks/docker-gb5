FROM ubuntu:20.04
LABEL jay94ks "kay94ks@gmail.com"

ARG DEBIAN_FRONTEND=noninteractive

RUN sed -i 's/archive.ubuntu.com/mirror.kakao.com/g' /etc/apt/sources.list \
    && apt-get update
	
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get upgrade -yq
RUN apt-get install -yq apache2 curl wget unzip git
RUN apt-get install -yq php7.4 php7.4-mysql libapache2-mod-php7.4
RUN apt-get install -yq php7.4-curl php7.4-gd php7.4-json php7.4-xml php7.4-mbstring php7.4-zip
RUN apt-get install -yq fuse-overlayfs
RUN a2enmod rewrite
EXPOSE 80

COPY ./entry.sh /root/entry.sh
COPY ./run-env.php /root/run-env.php
COPY ./security.conf /etc/apache2/conf-available/security.conf

COPY ./php-cli.ini /etc/php/7.4/cli/php.ini
COPY ./php-www.ini /etc/php/7.4/apache2/php.ini

COPY ./install-auto.php /root/install-auto.php

RUN chown root:root /root/entry.sh && chmod +x /root/entry.sh
ENTRYPOINT [ "/root/entry.sh" ]
