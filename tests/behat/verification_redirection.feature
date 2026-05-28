@local @local_external_users @javascript
Feature: External user verification gatekeeping
  In order to ensure external users are verified before browsing
  As an admin
  I need to redirect unverified external users to the verification page

  Background:
    Given the following config values are set as admin:
      | passwordpolicy |        0 |                      |
      | registerauth   | external |                      |
      | auth           | external |                      |
      | allowbrowsing  |        0 | local_external_users |
    And the following "users" exist:
      | username  | firstname  | lastname | email             | auth     | suspended | timecreated        | profile_field_external_user |
      | extveri   | Verified   |        1 | user1@example.com | external |         0 | ## 201 days ago ## |                           1 |
      | extunveri | Unverified |        1 | user2@example.com | external |         0 | ## 201 days ago ## |                           1 |
      | extlim    | Limited    |        1 | user3@example.com | external |         0 | ## 201 days ago ## |                           1 |
      | extexp    | Expired    |        1 | user4@example.com | external |         0 | ## 201 days ago ## |                           1 |
      | extrej    | Rejected   |        1 | user4@example.com | external |         0 | ## 201 days ago ## |                           1 |
    And I set the following custom profile field values:
      | username  | external_user_verified |
      | extveri   |                      1 |
      | extunveri |                      0 |
      | extlim    |             31.12.2030 |
      | extexp    |             31.12.2020 |
      | extrej    |                     -1 |

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

  Scenario: A rejected external user can not access the dashboard
    Given I log in as "extrej"
    Then I should see "You must first be verified"

  Scenario: A pending external user can not access the dashboard
    Given I set the following custom profile field values:
      | username | external_user_verified | external_user_pending |
      | extexp   |             31.12.2020 |                     1 |
    And I log in as "extexp"
    Then I should see "You must first be verified"

  Scenario: A pending external user may browse when allow‑browsing is enabled
    Given the following config values are set as admin:
      | allowbrowsing | 1 | local_external_users |
    And I set the following custom profile field values:
      | username | external_user_verified | external_user_pending |
      | extexp   |             31.12.2020 |                     1 |
    And I log in as "extexp"
    Then I should not see "You must first be verified"
    And I should not see "Registration Status"

  Scenario: A pending external user may not browse when allow‑browsing is disabled
    Given the following config values are set as admin:
      | allowbrowsing | 0 | local_external_users |
    And I set the following custom profile field values:
      | username | external_user_verified | external_user_pending |
      | extexp   |             31.12.2020 |                     1 |
    And I log in as "extexp"
    Then I should see "You must first be verified"

  Scenario: A browsing user cannot access the verification page to submit documents
    Given the following config values are set as admin:
      | allowbrowsing | 1 | local_external_users |
    And I set the following custom profile field values:
      | username  | external_user_pending |
      | extunveri |                     1 |
    And I log in as "extunveri"
    And I visit "/local/external_users/verify.php"
    Then I should not see "You must first be verified"

  Scenario: A browsing user cannot access the site when pending but allow-browsing disabled
    Given the following config values are set as admin:
      | allowbrowsing | 0 | local_external_users |
    And I set the following custom profile field values:
      | username  | external_user_pending |
      | extunveri |                     1 |
    And I log in as "extunveri"
    And I visit "/"
    Then I should see "You must first be verified"

  Scenario: A browsing user cannot access the site when not pending but allow-browsing allowed
    Given the following config values are set as admin:
      | allowbrowsing | 1 | local_external_users |
    And I set the following custom profile field values:
      | username  | external_user_pending |
      | extunveri |                     0 |
    And I log in as "extunveri"
    Then I should see "You must first be verified"

  Scenario: A user with verification expiring today cannot access the dashboard
    Given I set the following custom profile field values:
      | username | external_user_verified |
      | extlim   | ## today ##            |
    And I log in as "extlim"
    Then I should see "You must first be verified"

  Scenario: A verified user is redirected away from the verification page
    Given I log in as "extveri"
    And I visit "/local/external_users/verify.php"
    Then I should not see "You must first be verified"
    And the url should match "/"

  Scenario: A non-external user is not affected by gatekeeping
    Given the following "users" exist:
      | username | firstname | lastname | email              | auth   | suspended | timecreated        |
      | normal   | Normal    | User     | normal@example.com | manual |         0 | ## 201 days ago ## |
    And I log in as "normal"
    Then I should not see "You must first be verified"

  Scenario: Allow-browsing updates user profile fields for pending users
    Given the following config values are set as admin:
      | allowbrowsing        |       1 | local_external_users |
      | allowbrowsing_tariff | student | local_external_users |
    And I set the following custom profile field values:
      | username | external_user_verified | external_user_pending |
      | extexp   |             31.12.2020 |                     1 |
    And I log in as "extexp"
    Then I should not see "You must first be verified"

  Scenario: Redirects are skipped on excluded pages
    Given the following config values are set as admin:
      | redirect_excludes | /login,/user/profile | local_external_users |
    And I set the following custom profile field values:
      | username  | external_user_verified |
      | extunveri |                      0 |
    And I log in as "extunveri"
    And I visit "/user/profile.php"
    Then I should not see "You must first be verified"

  Scenario: Unverified external user with expired password is forced to change it and then blocked by gatekeeping
    Given the following config values are set as admin:
      | expiration     | 1  | auth_external |
      | expirationtime | 30 | auth_external |
    And the following "users" exist:
      | username   | firstname | lastname | email                 | auth     | suspended | password      | profile_field_external_user |
      | extpassexp | Expired   | Pass     | expassexp@example.com | external | 0         | Moodle@12345! | 1                           |
    And I set the following custom profile field values:
      | username   | external_user_verified |
      | extpassexp | 0                      |
    And the user "extpassexp" has the following preferences:
      | auth_external_passwordupdatetime | -2678400 |
    When I am on homepage
    And I click on "Log in" "link"
    And I set the field "Username" to "extpassexp"
    And I set the field "Password" to "Moodle@12345!"
    And I press "Log in"
    Then I should see "Your password has expired"
    And I click on "Continue" "button"
    And I set the field "Current password" to "Moodle@12345!"
    And I set the field "New password" to "Moodle@12345?"
    And I set the field "New password (again)" to "Moodle@12345?"
    And I press "Save changes"
    Then I should see "You must first be verified"

  Scenario: Verified external user with expired password is forced to change it and can then access dashboard
    Given the following config values are set as admin:
      | expiration     | 1  | auth_external |
      | expirationtime | 30 | auth_external |
    And the following "users" exist:
      | username   | firstname | lastname | email                  | auth     | suspended | password      | profile_field_external_user |
      | extveriexp | Verified  | Exp      | extveriexp@example.com | external | 0         | Moodle@12345! | 1                           |
    And I set the following custom profile field values:
      | username   | external_user_verified |
      | extveriexp | 1                      |
    And the user "extveriexp" has the following preferences:
      | auth_external_passwordupdatetime | -2678400 |
    When I am on homepage
    And I click on "Log in" "link"
    And I set the field "Username" to "extveriexp"
    And I set the field "Password" to "Moodle@12345!"
    And I press "Log in"
    Then I should see "Your password has expired"
    And I click on "Continue" "button"
    And I set the field "Current password" to "Moodle@12345!"
    And I set the field "New password" to "Moodle@12345?"
    And I set the field "New password (again)" to "Moodle@12345?"
    And I press "Save changes"
    Then I should see "Password has been changed"
    And I click on "Continue" "button"
    Then I should not see "You must first be verified"
