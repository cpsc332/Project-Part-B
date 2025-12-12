SHELL := /bin/bash

check: 
	find . -type f -name "*.php" -print0 | xargs -0 -n1 php -l

setup-codespace:
	sudo apt update
	sudo apt install  -y mariadb-server mariadb-client
	sudo service mariadb start
	sudo apt install -y php
	sudo apt install -y php-mysql

	sudo mysql -u root < sql/schema.sql
	php -m | grep -i mysql || true
	
#	After, run the following
# 	export PATH=/usr/bin:$PATH

fix:
	./vendor/bin/php-cs-fixer fix .