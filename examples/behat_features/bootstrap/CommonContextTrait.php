<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PHPUnit_Framework_Assert as Assertions;
use Doctrine\ORM\Tools\SchemaTool;

trait CommonContextTrait
{
    /**
     * @var Silex/Application
     */
    protected static $app;

    /**
     * @BeforeSuite
     */
    public static function bootstrapSilex(BeforeSuiteScope $scope)
    {
        if (!self::$app) {
            self::$app = require_once __DIR__ . '/../../src/app.php';
            require_once APP_DIR . 'config/test.php';
        }

        return self::$app;
    }

    /**
     * @Given /^I sleep "([\d]+)" seconds$/
     */
    public function iSleepSeconds($seconds)
    {
        sleep($seconds);
    }

    /**
     * @Given there is a empty database
     */
    public function thereIsAEmptyDatabase()
    {
        // Create SchemaTool
        $schema = new SchemaTool(self::$app['db']);

        $metadatas = self::$app['db']->getMetadataFactory()->getAllMetadata();

        $schema->dropSchema($metadatas);
        $schema->createSchema($metadatas);
    }

    /**
     * @Then stop
     */
    public function stop()
    {
        die();
    }
}