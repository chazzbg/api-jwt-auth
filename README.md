**Projects and Tasks API with JWT OAuth2 Authentication**
---
Requirements
1. `php >= 8.00`
2. `ext-ctype`
3. `ext-http`
4. `ext-iconv`
5. `sqlite >= 3`
5. `symfony cli`

How to install

1. ``composer install``
2. ``sqlite3 var/data.db "VACUUM";``
3. ``php bin/console doctrine:schema:create``

How to run

``symfony server:start ``

How to run tests

``php bin/phpunit --configuration phpunit.xml.dist``
