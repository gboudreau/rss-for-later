RSS-For-Later
=============

Read your RSS feeds in Pocket.

You can try it here: [http://rss-for-later.pommepause.com](http://rss-for-later.pommepause.com)

Requirements
------------
- PHP 5.2+, with cURL & SimpleXML extensions enabled
- MySQL

Installation
------------
- Upload all files to your host.
- Create a new database in your MySQL, and create a new user to access it:  
    ```CREATE DATABASE rss_for_later;
    GRANT ALL ON rss_for_later.* to 'rss_for_later_user'@'localhost' IDENTIFIED BY 'some_long_password';```
- Import *_install/db_schema-mysql.sql* into your new database.
- Copy *classes/Config.example.php* to *classes/Config.php*, and change its content to fit your installation.
