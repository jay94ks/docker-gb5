#!/bin/bash

CUSTOM_INIT="$@"
HERE=`pwd`
TARGET="$G5_GIT_TAG"

WWW="/var/www"
WWWROOT="/var/www/html"
WWWDATA="/var/www/html/data"
WWWRUN="/var/www/run"

if [ "$TARGET" != "" ]; then
	TARGET="v5.5.8.2"
fi

if [ ! -d "$WWWROOT" ]; then
	mkdir -pv "$WWWROOT"
fi

if [ ! -d "$WWWRUN" ]; then
	mkdir -pv "$WWWRUN"
fi

if [ ! -f "$WWWROOT/data/dbconfig.php" ]; then
	RUN_APPS_INIT='n'

	if [ ! -f "$WWWRUN/g5-tag" ]; then
		mkdir -pv /tmp/g5 && cd /tmp/g5
		git clone \
			-b "$TARGET" \
			--single-branch "https://github.com/gnuboard/gnuboard5.git" \
			--verbose .

		if [ "$?" != "0" ]; then
			rm -rf "$WWWRUN/g5-tag"
			rm -rf /tmp/g5
			exit 1
		fi

		echo "GIT sync done, copying files..."
		rm -rf /tmp/g5/.git
		rm -rf /tmp/g5/.git*

		mv -fv /tmp/g5/* "$WWWROOT/"
		rm -rf /tmp/g5

		echo "$TARGET" > "$WWWRUN/g5-tag"
	fi

	if [ ! -f "$WWWRUN/eb4-tag" ]; then
		mkdir -pv /tmp/eb4 && cd /tmp/eb4
		git clone \
			"https://github.com/eyoom/eyoom_builder_4.git" \
			--verbose .

		if [ "$?" != "0" ]; then
			rm -rf "$WWWRUN/eb4-tag"
			rm -rf /tmp/eb4
			exit 1
		fi

		echo "GIT sync done (eb4), copying files..."
		rm -rf /tmp/eb4/.git
		rm -rf /tmp/eb4/.git*

		cp -rvf /tmp/eb4/* "$WWWROOT/"
		rm -rf /tmp/eb4

		# --> eb4 doesn't publish any tags, so nothing to write.
		echo "??" > "$WWWRUN/eb4-tag"

		if [ -d "/apps" ]; then
			if [ -f "$WWWRUN/g5-www" ]; then
				rm -rf "$WWWROOT/install"
			fi

			echo "copying application files..."
			cp -Rf /apps/* "$WWW/"
			RUN_APPS_INIT='y'
		fi
	fi

	if [ ! -f "$WWWRUN/g5-www" ]; then
		if [ -d "$WWWROOT/install" ]; then
			if [ ! -f "/root/install-auto.php" ]; then
				echo "fatal: the g5 with eb4 installation maybe corrupted."
				exit 1;
			fi

			# copy custom installation script.
			cp -R /root/install-auto.php "$WWWROOT/eyoom/install/"
			mkdir -pv "$WWWROOT/data"

			chmod 777 "$WWWDATA"
			chown -R www-data:www-data "$WWWDATA"

			cd "$WWWROOT/eyoom/install"
			echo "executing automation script..."
			/usr/bin/php -q "$WWWROOT/eyoom/install/install-auto.php"

			if [ "$?" != "0" ]; then
				rm -rf "$WWWRUN/g5-www"
				echo "-> failed to install g5."
				exit 1
			fi

			# store 1 to remember the g5 installed.
			echo "-> g5 with eb4 installed successfully."
			echo "$WWWROOT" > "$WWWRUN/g5-www"

			# then, delete the installation files.
			rm -rf "$WWWROOT/eyoom/install"
			rm -rf "$WWWROOT/install"
			rm -rf /root/install-auto.php

			chmod 777 "$WWWDATA"
			chown -R www-data:www-data "$WWWDATA"
		fi
	fi

	if [ "$RUN_APPS_INIT" == 'y' ]; then
		if [ -f "$WWW/site-init.sh" ]; then
			chmod +x "$WWW/site-init.sh" && cd "$WWW"
			/bin/bash -c "$WWW/site-init.sh"
		fi

		chmod 777 "$WWWDATA"
		chown -R www-data:www-data "$WWWDATA"
	fi

elif [ -f "$WWWDATA/dbconfig.php" ]; then
	if [ -f "/root/install-auto.php" ]; then
		echo "the g5 installation detected, automation script disabled."
		rm -rf /root/install-auto.php
	fi
fi

cd $HERE
/usr/bin/php -q /root/run-env.php

if [ "$CUSTOM_INIT" != "" ];
then
	echo "executing the custom initialization script..."

	cd /var/www/html
	/usr/bin/php -q $CUSTOM_INIT

	echo "-> done."
fi

if [ -f "$WWW/site-up.sh" ]; then
	chmod +x "$WWW/site-up.sh" && cd "$WWW"
	/bin/bash -c "$WWW/site-up.sh"
fi

cd $HERE
/usr/sbin/apache2ctl -D FOREGROUND &
tail -f /var/log/apache2/error.log
