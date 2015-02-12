Testing workshop
================

## Install silex project and dependencies

```
$ cd PATH/vagrant
~/$ vagrant up
~/$ vagrant ssh
~/$ sudo su - www-data
~/$ cd /var/www/silex
~/$ composer create-project fabpot/silex-skeleton . ~2.0@dev
~/$ composer require guzzlehttp/guzzle='~4.0'
~/$ composer require --dev behat/behat='~3.0.6'
~/$ composer require --dev phpspec/phpspec='~2.0'
~/$ composer require --dev phpunit/phpunit='~3.7'
~/$ composer require --dev mikey179/vfsStream='1.4.*'
~/$ cd /var/www/silex/bin
~/$ ln -s ../vendor/bin/phpspec phpspec
~/$ ln -s ../vendor/bin/phpunit phpunit
~/$ ln -s ../vendor/bin/behat behat
```

## Documentation

# Behat
 - http://behat.readthedocs.org/en/v3.0/
 - http://blog.lepine.pro/images/2012-03-behat-cheat-sheet-en.pdf

# PHP Spec
 - http://www.phpspec.net/en/latest/manual/introduction.html
 - https://github.com/yvoyer/phpspec-cheat-sheet
 - https://speakerdeck.com/kikobolingas/diseno-emergente-con-phpspec-phpmad
 - http://techportal.inviqa.com/2014/09/11/my-top-ten-favourite-phpspec-limitations/