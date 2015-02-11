<?php

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Gigigo\Security\Authentication\Token\AccessTokenToken;
use PHPUnit_Framework_Assert as Assertions;

class ApiContext implements SnippetAcceptingContext
{
    use RestApiContextTrait;
    use CommonContextTrait;
    
    /**
     * @var array User credetials
     */
    protected $credentials;
    
    /**
     * @var stdClass Data in database
     */
    protected $data;

    /**
     * @var Object access token and refresh token if requested
     */
    protected $authorization;
    
    /**
     * Initializes context.
     *
     * Every scenario gets its own context object.
     * You can also pass arbitrary arguments to the context constructor through behat.yml.
     */
    public function __construct(array $clientConfig, array $credentials) 
    {
        // object to request api
        $this->setClient(new GuzzleHttp\Client($clientConfig));

        // user credentials
        $this->credentials = [
            "grantType"     => "password",
            "identifier"    => $credentials['user'],
            "pass"          => $credentials['pass']
        ];

        // init data saved object
        $this->initData();
    }

    protected function initData()
    {
        // data created
        $this->data = new stdClass();
        $this->data->users = [];
        $this->data->holders = [];
        $this->data->activities = [];
    }

    /**
     * @Given I am :device with version :version
     */
    public function iAmDeviceWithVersion($device, $version)
    {
        $value = strtoupper($device) . '_' . $version;

        $this->iRemoveHeader('X-app-version');
        $this->iSetHeaderWithValue('X-app-version', $value);
    }
    
    /**
     * Adds Bearer Authentication header to next request.
     *
     * @Given /^I am authenticating( stay signed)?( with cookie)?$/
     */
    public function iAmAuthenticating($staySigned = false, $cookie = false)
    {
        // logout if access token is present
        $this->iAmNotLoggedIn();

        // if request mark as staySigned then add this param to credentials
        if ($staySigned) {
            $this->credentials['staySigned'] = true;
        }
        // if request need cookie
        if ($cookie) {
            $this->credentials['createCookie'] = true;
        }

        // build body
        $body = new PyStringNode([json_encode($this->credentials)],0);

        // send post request to get an access_token
        $this->iSendARequestWithBody("POST", "/v1/security/token", $body);

        // get response body and set null actual response to prevent errors
        $actual = $this->response->json(['object'=>true]);

        // check request is correct
        Assertions::assertEquals('success', $actual->status);
        if ($cookie) {
            Assertions::assertArrayHasKey('Set-Cookie', $this->response->getHeaders());
            Assertions::assertStringStartsWith(AccessTokenToken::COOKIE_NAME . '=', $this->response->getHeaders()['Set-Cookie'][0]);
        }

        $this->response = null;

        // get authorization code from request
        $this->authorization = $actual->data;
        if ($cookie) {
            // cookie for next autenticated requests
            $this->addCookie(AccessTokenToken::COOKIE_NAME, $this->authorization->accessToken->value);
        }
        else {
            // header for next autenticated requests
            $this->addHeader('Authorization', 'Bearer ' . $this->authorization->accessToken->value);
        }

        // set tokens place holders
        if ($staySigned) {
            $this->setPlaceHolder('{{refresh_token}}', $this->authorization->refreshToken->value);
        }
        $this->setPlaceHolder('{{access_token}}', $this->authorization->accessToken->value);
    }
    
    /**
     * @Given I am not logged in
     */
    public function iAmNotLoggedIn()
    {
        $this->removeHeader('Authorization');
        $this->removeCookie(AccessTokenToken::COOKIE_NAME);
    }

    /**
     * @Given I get an expired access token
     */
    public function iGetAnExpiredAccessToken()
    {
        $user = self::$app['user.repository']->findOneByUsername('regular_user');
        $accessTokenNew = new \Gigigo\Model\Entity\AccessToken(['token_lifetime' => -5]);
        $accessTokenNew->setUser($user);

        $this->addHeader('Authorization', 'Bearer ' . $accessTokenNew->getValue());
        $this->authorization = null;

        self::$app['db']->persist($accessTokenNew);
        self::$app['db']->flush();
    }
    
    /**
     * @Given there is a :email user in database
     */
    public function thereIsAUserInDatabase($email)
    {
        $user = self::$app['user.repository']->create(
            $email,
            "test-user"
        );
        
        $this->data->users[$email] = $user;
    }
    
    /**
     * @Given there are users in database:
     */
    public function thereAreUsersInDatabase(TableNode $table)
    {
        foreach ($table as $user) {
            $this->data->users[$user['email']] = self::$app['user.repository']->create(
                $user['email'],
                $user['password'],
                $user['active'],
                $user['locked']
            );
        }
    }

    /**
     * @Then the response should contain updated access token requested
     */
    public function theResponseShouldContainUpdatedAccessTokenRequested()
    {
        Assertions::assertEquals($this->authorization->accessToken->value, $this->response->json()['data']['accessToken']['value']);

        $accessToken = self::$app['access_token.repository']->findOneByValue($this->authorization->accessToken->value);

        // assert that access token has updated it last access datetime
        Assertions::assertNotEmpty($accessToken->getLastAccess());
    }

    /**
     * @Then /^there (?:are|is) ([\d]+) items? in "([^"]*)" table$/
     */
    public function thereAreItemsInCollection($numberItems, $table)
    {
        $items = self::$app['db']->getRepository('g2m:'.$table)
            ->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute();

        Assertions::assertEquals($numberItems, $items);
    }

}
