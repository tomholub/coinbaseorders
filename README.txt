coinbaseorders
==============

Coinbase Limit Orders app

The app is based on Nette Framework http://nette.org/ . Nette was chosen for its security. 
http://doc.nette.org/en/2.1/vulnerability-protection

Currently it relies on independent cron opening a page /?presenter=Api&action=cron once every 
minute to check orders. If you installed the app locally, you need to set up a cron such as this:

*/1 * * * * curl -o "/home/user/bitcoin/cron-`date`.html" "http://local.localurl.com/?presenter=Api&action=cron"
