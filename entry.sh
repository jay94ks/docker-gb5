#!/bin/bash

export TMP = "$@" | tr -d ' '
export HERE = `pwd`

/usr/bin/php -q /root/run-env.php

if [ "$TMP" != "" ];
then
	cd /var/www/html
	/usr/bin/php -q $TMP
fi

cd $HERE
/usr/sbin/apache2ctl -D FOREGROUND
