@general
Feature: Manage general api requests
    In order to access this api
    As a dumb user
    I want to test some general requests

    Background:
        Given there is a empty database
        Given there is a "test-user@gigigo.local" user in database
        Given I am android with version 1.0.0

    Scenario: Get configuration
        Given I am not logged in
         When I send a GET request to "/v1/configuration"
         Then the response code should be 200

    Scenario: Wrog POST
        Given I am authenticating
         When I send a POST request to "/v1/configuration" with body:
            """
            no json body
            """
         Then the response code should be 400
         Then the response should contain json:
            """
            {"status":"error","message":"Malformed json","code":400}
            """

    Scenario: App version header not set
        Given I remove header "X-app-version"
        Given I am not logged in
         When I send a GET request to "/v1/configuration"
         Then the response code should be 400
         Then the response should contain json:
            """
            {"status":"error","message":"App version header not setted","code":10020}
            """

    Scenario: App version header invalid
        Given I remove header "X-app-version"
        Given I set header "X-app-version" with value "not-valid-at-all"
        Given I am not logged in
         When I send a GET request to "/v1/configuration"
         Then the response code should be 400
         Then the response should contain json:
            """
            {"status":"error","message":"App version header is invalid","code":10021}
            """

    Scenario Outline: App version header invalid device and version
        Given I am <device> with version <version>
        Given I am not logged in
         When I send a GET request to "/v1/configuration"
         Then the response code should be <httpStatus>
          And the response should contain partial json:
            """
            <json>
            """

        Examples:
            | device  | version | httpStatus | json                 |
            | wrongDe | 1.0.0   | 400        | {"code":10022}       |
            | ios     | 0.0.1   | 400        | {"code":10023}       |
            | ios     | 1.0.0   | 200        | {"status":"success"} |
            | android | 0.0.1   | 400        | {"code":10023}       |
            | android | 1.0.1   | 200        | {"status":"success"} |

