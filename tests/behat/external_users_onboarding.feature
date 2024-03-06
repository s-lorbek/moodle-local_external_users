Feature: User Signup

  Scenario: Signup as a new user
    Given I am on the signup page
    When I fill in "Email address" with "newuser@example.com"
    And I fill in "Email (again)" with "newuser@example.com"
    And I fill in "Password" with "Password1000#"
    And I fill in "Password (again)" with "Password1000"
    And I fill in "First name" with "John"
    And I fill in "Last name" with "Doe"
    And I fill in "City/town" with "Graz"
    And I select "Austria" from "Country"
    And I press "Create my new account"
    Then I should be on the login page
