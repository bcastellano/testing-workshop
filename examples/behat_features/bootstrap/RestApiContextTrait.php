<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PHPUnit_Framework_Assert as Assertions;

trait RestApiContextTrait
{

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @var array
     */
    protected $cookies = array();

    /**
     * @var array
     */
    protected $urlParameters = array();

    /**
     * @var \GuzzleHttp\Message\RequestInterface
     */
    protected $request;

    /**
     * @var \GuzzleHttp\Message\ResponseInterface
     */
    protected $response;

    protected $placeHolders = array();

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }    

    /**
     * Sets a HTTP Header.
     *
     * @param string $name  header name
     * @param string $value header value
     *
     * @Given /^I set header "([^"]*)" with value "([^"]*)"$/
     */
    public function iSetHeaderWithValue($name, $value)
    {
        $this->addHeader($name, $value);
    }
    
    /**
     * Remove a HTTP Header.
     *
     * @param string $name  header name
     *
     * @Given /^I remove header "([^"]*)"$/
     */
    public function iRemoveHeader($name)
    {
        $this->removeHeader($name);
    }

    /**
     * Sets a HTTP Cookie.
     *
     * @param string $name  cookie name
     * @param string $value cookie value
     *
     * @Given /^I set cookie "([^"]*)" with value "([^"]*)"$/
     */
    public function iSetCookieWithValue($name, $value)
    {
        $this->addCookie($name, $value);
    }

    /**
     * Remove a HTTP Cookie.
     *
     * @param string $name cookie name
     *
     * @Given /^I remove cookie "([^"]*)"$/
     */
    public function iRemoveCookie($name)
    {
        $this->removeCookie($name);
    }

    /**
     * Add parameter to replace in url string with a fixed value
     *
     * @Given /^I set url parameter "([^"]*)" with value "([^"]*)"$/
     */
    public function iSetUrlParameterWithValue($name, $value)
    {
        $this->addUrlParameter($name, $value);
    }

    /**
     * Add parameter to replace in url string with a expresion result
     *
     * @Given /^I set url parameter "([^"]*)" with expression "([^"]*)"$/
     */
    public function iSetUrlParameterWithExpression($name, $expression)
    {
        $value = '';
        if (! empty($expression)) {
            eval("\$value = $expression;");
        }

        $this->addUrlParameter($name, $value);
    }

    /**
     * Set place holder string with value
     *
     * @Given /^I set place holder "([^"]*)" with value "([^"]*)"$/
     */
    public function iSetPlaceHolderWithValue($key, $value)
    {
        $this->setPlaceHolder($key, $value);
    }

    /**
     * Set place holder string with a expresion result
     *
     * @Given /^I set place holder "([^"]*)" with expression "([^"]*)"$/
     */
    public function iSetPlaceHolderWithExpression($key, $expression)
    {
        $value = '';
        if (! empty($expression)) {
            eval("\$value = $expression;");
        }

        $this->setPlaceHolder($key, $value);
    }

    /**
     * Sends HTTP request to specific relative URL.
     *
     * @param string $method request method
     * @param string $url    relative url
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)"$/
     */
    public function iSendARequest($method, $url)
    {
        $url = $this->prepareUrl($url);
        $this->request = $this->client->createRequest($method, $url);

        $this->setCookieHeader();
        if (!empty($this->headers)) {
            $this->request->addHeaders($this->headers);
        }

        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with field values from Table.
     *
     * @param string    $method request method
     * @param string    $url    relative url
     * @param TableNode $post   table of post values
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with values:$/
     */
    public function iSendARequestWithValues($method, $url, TableNode $post)
    {
        $url = $this->prepareUrl($url);
        $fields = array();

        foreach ($post->getRowsHash() as $key => $val) {
            $fields[$key] = $this->replacePlaceHolder($val);
        }

        $bodyOption = array(
          'body' => json_encode($fields),
        );
        $this->request = $this->client->createRequest($method, $url, $bodyOption);

        $this->setCookieHeader();
        if (!empty($this->headers)) {
            $this->request->addHeaders($this->headers);
        }

        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with raw body from PyString.
     *
     * @param string       $method request method
     * @param string       $url    relative url
     * @param PyStringNode $string request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with body:$/
     */
    public function iSendARequestWithBody($method, $url, PyStringNode $string)
    {
        $url = $this->prepareUrl($url);
        $string = $this->replacePlaceHolder(trim($string));

        $this->setCookieHeader();
        $this->request = $this->client->createRequest(
            $method,
            $url,
            array(
                'headers' => $this->getHeaders(),
                'body' => $string,
            )
        );
        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with form data from PyString.
     *
     * @param string       $method request method
     * @param string       $url    relative url
     * @param PyStringNode $body   request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with form data:$/
     */
    public function iSendARequestWithFormData($method, $url, PyStringNode $body)
    {
        $url = $this->prepareUrl($url);
        $body = $this->replacePlaceHolder(trim($body));

        $fields = array();
        parse_str(implode('&', explode("\n", $body)), $fields);
        $this->request = $this->client->createRequest($method, $url);
        /** @var \GuzzleHttp\Post\PostBodyInterface $requestBody */
        $requestBody = $this->request->getBody();
        foreach ($fields as $key => $value) {
            $requestBody->setField($key, $value);
        }

        $this->setCookieHeader();
        if (!empty($this->headers)) {
            $this->request->addHeaders($this->headers);
        }

        $this->sendRequest();
    }

    /**
     * Checks that response has specific status code.
     *
     * @param string $code status code
     *
     * @Then /^(?:the )?response code should be (\d+)$/
     */
    public function theResponseCodeShouldBe($code)
    {
        $expected = intval($code);
        $actual = intval($this->response->getStatusCode());
        Assertions::assertSame($expected, $actual);
    }

    /**
     * Checks that response body contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should contain "([^"]*)"$/
     */
    public function theResponseShouldContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/i';
        $actual = (string) $this->response->getBody();
        Assertions::assertRegExp($expectedRegexp, $actual);
    }

    /**
     * Checks that response body doesn't contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should not contain "([^"]*)"$/
     */
    public function theResponseShouldNotContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/';
        $actual = (string) $this->response->getBody();
        Assertions::assertNotRegExp($expectedRegexp, $actual);
    }

    /**
     * Checks that response body contains JSON from PyString.
     *
     * Do not check that the response body /only/ contains the JSON from PyString,
     *
     * @param PyStringNode $jsonString
     *
     * @throws \RuntimeException
     *
     * @Then /^(?:the )?response should contain json:$/
     */
    public function theResponseShouldContainJson(PyStringNode $jsonString)
    {
        $etalon = json_decode($this->replacePlaceHolder($jsonString->getRaw()), true);
        $actual = $this->response->json();

        if (null === $etalon) {
            throw new \RuntimeException(
              "Can not convert etalon to json:\n" . $this->replacePlaceHolder($jsonString->getRaw())
            );
        }

        Assertions::assertGreaterThanOrEqual(count($etalon), count($actual));
        foreach ($etalon as $key => $needle) {
            Assertions::assertArrayHasKey($key, $actual);
            Assertions::assertEquals($etalon[$key], $actual[$key]);
        }
    }

    /**
     * Checks that response body contains partial data of JSON from PyString.
     *
     * @param PyStringNode $jsonString
     *
     * @throws \RuntimeException
     *
     * @Then /^(?:the )?response should contain partial json:$/
     */
    public function theResponseShouldContainPartialJson(PyStringNode $jsonString)
    {
        $etalon = json_decode($this->replacePlaceHolder($jsonString->getRaw()), true);
        $actual = $this->response->json();

        if (null === $etalon) {
            throw new \RuntimeException(
                "Can not convert etalon to json:\n" . $this->replacePlaceHolder($jsonString->getRaw())
            );
        }

        Assertions::assertGreaterThanOrEqual(count($etalon), count($actual));

        // matches the deepest value
        $this->iterateKeys($etalon, $actual);
    }
    
    /**
     * @Then /^(?:the )?response headers has "([^"]*)" header$/
     */
    public function theResponseHeadersHasHeader($headerName)
    {
        Assertions::assertTrue($this->response->hasHeader($headerName));
    }

    /**
     * @Then /^(?:the )?response headers has "([^"]*)" header with value "([^"]*)"$/
     */
    public function theResponseHeadersHasHeaderWithValue($headerName, $headerValue)
    {
        Assertions::assertEquals($this->response->getHeader($headerName), $headerValue);
    }

    /**
     * Prints last response body.
     *
     * @Then print response
     */
    public function printResponse()
    {
        $request = $this->request;
        $response = $this->response;

        echo sprintf(
            "%s %s => %d:\n%s",
            $request->getMethod(),
            $request->getUrl(),
            $response->getStatusCode(),
            $response->getBody()
        );
    }

    /**
     * Prepare URL by replacing placeholders and trimming slashes.
     *
     * @param string $url
     *
     * @return string
     */
    private function prepareUrl($url)
    {
        return ltrim($this->replaceUrlParameters($this->replacePlaceHolder($url)), '/');
    }

    /**
     * Sets place holder for replacement.
     *
     * you can specify placeholders, which will
     * be replaced in URL, request or response body.
     *
     * @param string $key   token name
     * @param string $value replace value
     */
    public function setPlaceHolder($key, $value)
    {
        $this->placeHolders[$key] = $value;
    }

    /**
     * Replaces placeholders in provided text.
     *
     * @param string $string
     *
     * @return string
     */
    protected function replacePlaceHolder($string)
    {
        foreach ($this->placeHolders as $key => $val) {
            $string = str_replace($key, $val, $string);
        }

        return $string;
    }

    /**
     * Returns headers, that will be used to send requests.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Adds header
     *
     * @param string $name
     * @param string $value
     */
    protected function addHeader($name, $value)
    {
        if (isset($this->headers[$name])) {
            if (!is_array($this->headers[$name])) {
                $this->headers[$name] = array($this->headers[$name]);
            }

            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Removes a header identified by $headerName
     *
     * @param string $headerName
     */
    protected function removeHeader($headerName)
    {
        if (array_key_exists($headerName, $this->headers)) {
            unset($this->headers[$headerName]);
        }
    }
    
    /**
     * Check if header key exists
     * 
     * @param string $headerName
     * @return boolean
     */
    protected function hasHeader($headerName)
    {
        return isset($this->headers[$headerName]);
    }

    /**
     * Returns cookies, that will be used to send requests.
     *
     * @return array
     */
    protected function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Adds cookie
     *
     * @param string $name
     * @param string $value
     */
    protected function addCookie($name, $value)
    {
        $this->cookies[$name] = $value;
    }

    /**
     * Removes a header identified by $headerName
     *
     * @param string $headerName
     */
    protected function removeCookie($cookieName)
    {
        if (array_key_exists($cookieName, $this->cookies)) {
            unset($this->cookies[$cookieName]);
        }
    }

    /**
     * Check if header key exists
     *
     * @param string $headerName
     * @return boolean
     */
    protected function hasCookie($cookieName)
    {
        return isset($this->cookies[$cookieName]);
    }

    /**
     * Add cookie array to headers for the next request
     */
    protected function setCookieHeader()
    {
        // if there are cookies
        if ($this->cookies) {
            // convert to string for header "key=value;key=value;...."
            $cookies = implode(';', array_map(
                function($k, $v) {
                    return "$k=$v";
                },
                array_keys($this->cookies),
                $this->cookies)
            );

            // remove old cookie header to update it
            $this->removeHeader('Cookie');
            $this->addHeader('Cookie', $cookies);
        }
    }

    /**
     * Adds url parameter
     *
     * @param string $name
     * @param string $value
     */
    protected function addUrlParameter($name, $value)
    {
        $this->urlParameters[$name] = $value;
    }

    /**
     * Replaces url parameters in provided text.
     *
     * @param string $string
     *
     * @return string
     */
    protected function replaceUrlParameters($string)
    {
        foreach ($this->urlParameters as $key => $val) {
            $string = str_replace($key, $val, $string);
        }

        return $string;
    }

    /**
     * Iterate recursively a pair of arrays to check if last value (deepest) matches between both arrays
     *
     * @param $etalon
     * @param $actual
     */
    private function iterateKeys($etalon, $actual)
    {
        foreach ($etalon as $key => $needle) {
            Assertions::assertArrayHasKey($key, $actual);

            if (is_array($needle) || is_object($needle)) {
                $this->iterateKeys($needle, $actual[$key]);
            }
            else {
                Assertions::assertEquals($etalon[$key], $actual[$key]);
            }
        }
    }

    private function sendRequest()
    {
        try {
            $this->response = $this->client->send($this->request);
        } catch (RequestException $e) {
            $this->response = $e->getResponse();

            if (null === $this->response) {
                throw $e;
            }
        }
    }
}