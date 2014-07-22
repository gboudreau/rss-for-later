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

    ```
    CREATE DATABASE rss_for_later;
    GRANT ALL ON rss_for_later.* to 'rss_for_later_user'@'localhost' IDENTIFIED BY 'some_long_password';
    ```
- Import *_install/db_schema-mysql.sql* into your new database.
- Copy *classes/Config.example.php* to *classes/Config.php*, and change its content to fit your installation.
- Create a cron to update feeds:

    ```
    # Pocket RSS
    */15 * * * *    curl -s 'http://rss-for-later.pommepause.com/cron/' > /dev/null
    ```

Notes to self
-------------
[Many ideas on how to improve RSS fetching](http://inessential.com/2013/03/18/brians_stupid_feed_tricks)

License
-------
Copyright 2013-2014 Guillaume Boudreau

This file is part of RSS-For-Later.

RSS-For-Later is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

RSS-For-Later is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with RSS-For-Later.  If not, see <http://www.gnu.org/licenses/>.
