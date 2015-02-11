@security
Feature: Manage Authorization and Authentication process
    In order to access this api
    As a logged user
    I want to get a set of tokens to interchange with next request with server

    Background:
        Given there is a empty database
        Given there is a "test-user@gigigo.local" user in database
        Given there are users in database:
            | email         | password | active | locked |
            | regular_user  | pass     | 1      | 0      |
            | locked_user   | pass     | 1      | 1      |
            | disabled_user | pass     | 0      | 0      |
        Given I am ios with version 1.0.0

    Scenario: Secured endpoint as unlogged user
        Given I am not logged in
         When I send a GET request to "/v1/security/user"
         Then the response code should be 401

    Scenario: Expired access token
        Given I am not logged in
        And I get an expired access token
        When I send a GET request to "/v1/security/user"
        Then the response code should be 401
        And the response should contain "Bad credentials (Access token expired)."

    Scenario: Secured endpoint with wrong access token
        Given I am not logged in
        And I set header "Authorization" with value "notValidAccessToken"
        When I send a GET request to "/v1/security/user"
        Then the response code should be 401

    Scenario: Get access token with password grant type
        Given I am not logged in
         When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "password",
                "identifier": "test-user@gigigo.local",
                "pass": "test-user",
                "staySigned": true
            }
            """
         Then the response code should be 200
          And the response should contain "accessToken"
          And the response should contain "refreshToken"
          And the response should contain "email"

    Scenario: Get access token with password grant type and invalid credentials (username)
        Given I am not logged in
        When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "password",
                "identifier": "notvaliduser@gigigo.local",
                "pass": "test-user"
            }
            """
        Then the response code should be 401
        And the response should contain "Bad credentials (Username"

    Scenario: Get access token with password grant type and invalid credentials (password)
        Given I am not logged in
        When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "password",
                "identifier": "test-user@gigigo.local",
                "pass": "invalid-password"
            }
            """
        Then the response code should be 401
        And the response should contain "Bad credentials (The presented password is invalid.)"

    Scenario: Get access token with password grant type and no password presented
        Given I am not logged in
        When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "password",
                "identifier": "test-user@gigigo.local",
                "pass": ""
            }
            """
        Then the response code should be 401
        And the response should contain "Bad credentials (The presented password cannot be empty.)"

    Scenario: Get user data
        Given I am authenticating
         When I send a GET request to "/v1/security/user"
         Then the response code should be 200
          And the response should contain "email"

    Scenario: Invalidate token (logout) as unlogged user
        Given I am not logged in
         When I send a GET request to "/v1/security/token/invalidate"
         Then the response code should be 401

    Scenario: Invalidate token (logout) as logged user
        Given I am authenticating
         When I send a GET request to "/v1/security/token/invalidate"
         When I send a GET request to "/v1/security/user"
         Then the response code should be 401

    Scenario: Get access token with password grant type and no active user
        Given I am not logged in
         When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "password",
                "identifier": "disabled_user",
                "pass": "pass"
            }
            """
         Then the response code should be 401
          And the response should contain "Bad credentials (Disabled)"

    Scenario: Get access token with password grant type and locked user
        Given I am not logged in
         When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "password",
                "identifier": "locked_user",
                "pass": "pass"
            }
            """
         Then the response code should be 401
          And the response should contain "Bad credentials (Locked)"

    Scenario: Try to login with not valid grant type
        Given I am not logged in
        When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "not_valid_grant_type",
                "identifier": "some_user_identifier",
                "pass": "pass"
            }
            """
        Then the response code should be 401
        And the response should contain "Bad credentials (Grant type not supported)"

    Scenario: Try to login with refresh token and invalid one
        Given I am not logged in
        When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "refresh_token",
                "refreshToken": "invalid refresh token"
            }
            """
        Then the response code should be 401
        And the response should contain "Bad credentials (Token"

    Scenario: Try to login with refresh token and valid one
        Given I am authenticating stay signed
        When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "refresh_token",
                "refreshToken": "{{refresh_token}}"
            }
            """
        Then the response code should be 200
        And the response should contain "accessToken"

    Scenario: Try to login with authorization code
        Given I am authenticating
        When I send a POST request to "/v1/security/token" with body:
            """
            {
                "grantType": "authorization_code"
            }
            """
        Then the response code should be 200
        And the response should contain updated access token requested

    Scenario: Login with cookie
        Given I am authenticating with cookie
         When I send a GET request to "/v1/security/user"
         Then the response code should be 200
          And the response should contain "email"

