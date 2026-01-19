@local @local_external_users @javascript
Feature: External User Hook Redirection Logic
  As a Moodle site administrator
  I want to ensure the 'onload' hook correctly redirects unverified users
  based on their profile status and the 'allowbrowsing' setting.

   Background:
    # Create all users and courses first (data generators)
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And I log in as "admin"
    And I visit "/user/profile/index.php"
    And I create the following custom profile fields:
      | shortname | datatype |
      | external_user | text |
      | external_user_verified | text |
      | external_user_comment | text |
      | eduPersonScopedAffiliation | text |
    # 2. Define the target verification page for clear assertions
    And the "local/external_users:views/verification.php" page is available

    # 3. Apply the external status to the test user
    And the following "custom profile field data" exists:
      | user | field | value |
      | unverifieduser | external_user | 1 |
      | verifieduser | external_user | 1 |

    Scenario: Redirect unverified user when browsing is disabled
      Given the following config values are set as:
          | local_external_users | allowbrowsing | 0 |
      # Set user status to '0' (unverified/pending)
      And the "external_user_verified" custom profile field data for "unverifieduser" is set to "0"

      And I log in as "unverifieduser"
      # Attempt to navigate to a standard Moodle page
      When I am on "Test Course" course homepage

      # Assertion: The browser lands on the verification page
      Then I should be on the "Verification" page