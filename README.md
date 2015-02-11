TESTING WORKSHOP
================

## Install silex project and dependencies

```
$ cd PATH/vagrant
~/vagrant$ vagrant up
~/vagrant$ cd ../silex
~/silex$ composer create-project fabpot/silex-skeleton . ~2.0@dev
~/silex$ composer require guzzlehttp/guzzle='~4.0'
~/silex$ composer require --dev behat/behat='~3.0.6'
~/silex$ composer require --dev phpspec/phpspec='~2.0'
~/silex$ composer require --dev phpunit/phpunit='~3.7'
~/silex$ composer require --dev mikey179/vfsStream='1.4.*'
~/silex$ cd bin
~/silex/bin$ ln -s ../vendor/bin/phpspec phpspec
~/silex/bin$ ln -s ../vendor/bin/phpunit phpunit
~/silex/bin$ ln -s ../vendor/bin/behat behat
```