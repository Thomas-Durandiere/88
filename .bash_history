docker-compose exec php ls -ld var/cache
ls -ld var/cache
chown -R www-data:www-data var/cache var/log
chown -R www-data:www-data var/cache var/log
docker-php-ext-install bcmath
php bin/phpunit tests/ContactTypeTest.php
echo $APP_ENV
php bin/console doctrine:database:create --env=test
php -v
php bin/console doctrine:database:create --env=test
docker exec -it <nom_container_db> mysql -u root -p
php bin/console doctrine:schema:create --env=test
php bin/phpunit tests/ContactTypeTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/LoginControllerTest.php
php bin/phpunit tests/MeteoTest.php
php bin/phpunit tests/OrderRepositoryTest.php
php bin/phpunit tests/OrderTest.php
php bin/phpunit tests/PhotoTest.php
php bin/phpunit tests/ProductsTypeTest.php
php bin/phpunit tests/RegistrationControllerTest.php
php bin/phpunit tests/UserTest.php
php bin/phpunit test/ContactTypeTest.php
php bin/phpunit test/LoginControllerTest.php
php bin/phpunit test/LoginControllerTest.php
php bin/phpunit tests/UserTest.php
php bin/phpunit tests/ContactTypeTest.php
php bin/phpunit tests/LoginControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit tests/HomeControllerTest.php
php bin/phpunit --coverage-html var/coverage
php -m | grep xdebug
pecl install xdebug
pecl channel-update pecl.php.net
rm -rf var/cache/*
