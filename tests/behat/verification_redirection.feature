@local @local_external_users @javascript
Feature: External user verification gatekeeping
  In order to ensure external users are verified before browsing
  As an admin
  I need to redirect unverified external users to the verification page

  Background:
    Given the following config values are set as admin:
      | passwordpolicy  | 0     |
      | registerauth    | external |
      | auth            | external |
    And the following "users" exist:
      | username | firstname | lastname | email             | auth   | suspended | timecreated        | profile_field_external_user |
      | extveri | Verified      | 1     | user1@example.com    | external | 0         | ## 201 days ago ## | 1                      |
      | extunveri | Unverified  | 1     | user2@example.com    | external | 0         | ## 201 days ago ## | 1                      |
      | extlim | Limited        | 1     | user3@example.com    | external | 0         | ## 201 days ago ## | 1                      |
      | extexp | Expired        | 1     | user4@example.com    | external | 0         | ## 201 days ago ## | 1                      |
      | extrej | Rejected        | 1     | user4@example.com    | external | 0         | ## 201 days ago ## | 1                     |
    And  I set the following custom profile field values:
      | username    | external_user_verified  |
      | extveri     | 1                       |
      | extunveri   | 0                       |
      | extlim      | 31.12.2030              |
      | extexp      | 31.12.2020              |
      | extrej     | -1                      |
  Scenario: A verified external user can access the dashboard
    Given I log in as "extveri"
    Then I should not see "You must first be verified"

  Scenario: A limited external user can access the dashboard
    Given I log in as "extlim"
    Then I should not see "You must first be verified"

  Scenario: An unverified external user can not access the dashboard
    Given I log in as "extunveri"
    Then I should see "You must first be verified"

  Scenario: An expired external user can not access the dashboard
    Given I log in as "extexp"
    Then I should see "You must first be verified"

  Scenario: An Rejected external user can not access the dashboard
    Given I log in as "extrej"
    Then I should see "You must first be verified"
