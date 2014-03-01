coinbaseorders
==============

Coinbase Limit Orders app
http://coinbaseorders.com/

The app is based on Nette Framework http://nette.org/ . Nette was chosen for its security: http://doc.nette.org/en/2.1/vulnerability-protection

PHP >= 5.3 is required.

*** FIRST RUN
1, Make directories `temp` and `log` writable.
2, Copy /app/config/config.local.neon.sample to /app/config/config.local.neon
3, Create a new MySQL database and run database-setup.sql on it
4, Edit the newly copied file /app/config/config.local.neon:
	- set your local MySQL settings
	- set your Coinbase Application API keys and URL. Get these on Coinbase.com. You might need to create a new application
	- set some random SALT
5, Set up your vhost to point to `www/www` directory (where index.php file is located)
    - open your browser and verify that you can access the homepage and the donate sub-page

*** CRON
Currently the app relies on independent cron opening a page "/api/cron" once a minute to check orders. If you installed the app locally, you need to set up a cron such as this:
*/1 * * * * wget http://yourdomain.com/api/cron


*** DEV ENVIRONMENT
I use Netbeans 7.2 with a Nette Framework extension.

*** Tips for installing on Mac OS X
- Installing PHP, MySQL, and Apache: http://jason.pureconcepts.net/2012/10/install-apache-php-mysql-mac-os-x/
- Installing mcrypt: http://coolestguidesontheplanet.com/install-mcrypt-php-mac-osx-10-9-mavericks-development-server/
