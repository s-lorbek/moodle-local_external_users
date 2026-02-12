@local @local_external_users @javascript
Feature: External user verification gatekeeping
  In order to ensure external users are verified before browsing
  As an admin
  I need to redirect unverified external users to the verification page

  Background:
    Given the following "custom profile fields" exist:
      | datatype | shortname               | name                     |
      | checkbox | external_user           | External User            |
      | text     | external_user_verified  | External User Verified   |
    And the following "users" exist:
      | username | firstname | profile_field_external_user | profile_field_external_user_verified |
      | extuser1 | Verified  | 1                           | 1                                    |
      | extuser2 | Expired   | 1                           | 0                                    |
      | reguser  | Regular   | 0                           |                                      |

  Scenario: A verified external user can access the dashboard
    Given I log in as "extuser1"
    When I am on site homepage
    Then I should not see "You must first be verified"

  Scenario: An expired external user is forced to the verification page
    Given I log in as "extuser2"
    When I am on homepage
    Then the url should match ".*verification.php.*"
  Scenario: A regular user is never redirected
    Given I log in as "reguser"
    When I am on homepage
    Then I should see "Site announcements"
    And the current page address should not contain "verification.php"