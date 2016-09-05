Introduction to Unit Testing with WordPress:
http://codesymphony.co/writing-wordpress-plugin-unit-tests/

Preparation
===========

1. PHPUnit

Install PHPUnit (http://phpunit.de/manual/current/en/installation.html)

2. WordPress-Dev

Get latest version of the wordpress-dev trunk (or make sure it exists).
It goes in the same directory where this wordpress installation is located.
The dev trunk must be called "wordpress-develop".

	Example
	-------

	If this is the current WordPress installation:
	/srv/www/wp-demo/wp-config.php
	/srv/www/wp-demo/wp-content/plugins/hello/@tests/readme.txt

	Then put the wordpress-develop installation here:
	/srv/www/wordpress-develop/trunk


	Get the develop trunk
	---------------------

	$ mkdir /srv/www/wordpress-develop
	$ cd /srv/www/wordpress-develop
	$ svn co http://develop.svn.wordpress.org/trunk/
	$ cd trunk
	$ svn up


Run the tests
=============

Run via `grunt test` from the plugin root directory.