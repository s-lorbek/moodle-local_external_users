@local @local_external_users @javascript
Feature: Test admin user management capabilities
  In order to manage external users
  As an admin
  I need to review, approve, reject, and revoke external users

  Background:
    Given the following config values are set as admin:
      | passwordpolicy       | 0                      |                      |
      | registerauth         | external               |                      |
      | auth                 | external               |                      |
      | allowbrowsing        | 0                      | local_external_users |
      | affiliations         | Uni,FH,None            | local_external_users |
      | price_category_order | external,student,staff | local_external_users |
      | theme                | musi                   |                      |
      | preset               | usi_graz               | theme_musi           |
      | logoplacement        | embedded               | theme_musi           |
    And the following "custom profile fields" exist:
      | datatype | shortname                  | name                       |
      | text     | eduPersonScopedAffiliation | eduPersonScopedAffiliation |
    And the following "users" exist:
      | username      | firstname | lastname | email                 | auth     | suspended | timecreated        | profile_field_external_user |
      | userpending1  | Pending   | One      | pending1@example.com  | external | 0         | ## 201 days ago ## | 1                           |
      | userpending2  | Pending   | Two      | pending2@example.com  | external | 0         | ## 201 days ago ## | 1                           |
      | userpending3  | Pending   | Three    | pending3@example.com  | external | 0         | ## 201 days ago ## | 1                           |
      | userpending4  | Pending   | Four     | pending4@example.com  | external | 0         | ## 201 days ago ## | 1                           |
      | userapproved1 | Approved  | One      | approved1@example.com | external | 0         | ## 201 days ago ## | 1                           |
    And I set the following custom profile field values:
      | username      | external_user_verified | external_user_pending |
      | userpending1  | 0                      | 1                     |
      | userpending2  | 0                      | 1                     |
      | userpending3  | 0                      | 1                     |
      | userpending4  | 0                      | 1                     |
      | userapproved1 | 1                      | 0                     |

  Scenario: Admin performs regular approval on a pending user
    Given I log in as "admin"
    And I change window size to "large"
    And I visit "/local/external_users/manage.php"
    Then I should see "Waiting for approval"
    When I press "Waiting for approval"
    Then I should see "userpending1"
    # Navigate to the user profile
    When I click on "userpending1" "link" in the "#sortabletablepending" "css_element"
    Then I should see "Pending"
    And I should see "One"
    And I should see "Academic affiliation:"
    And I should see "Actions & Controls"
    # Select Academic affiliation
    When I set the field "Academic affiliation" to "Uni"
    And I set the field "eduScope (Tariff):" to "student"
    And I press "Approve"
    # Check that we are redirected back to the referer list and userpending1 is not there
    Then I should see "Waiting for approval"
    And I should not see "userpending1" in the "#sortabletablepending" "css_element"
    # Check that userpending1 is now in the approved list
    And I visit "/local/external_users/manage.php"
    When I press "Approved"
    Then I should see "userpending1" in the "#sortabletableapproved" "css_element"

  Scenario: Admin performs limited approval on a pending user
    Given I log in as "admin"
    And I change window size to "large"
    And I visit "/local/external_users/manage.php"
    When I press "Waiting for approval"
    # Navigate to the user profile
    And I click on "userpending2" "link" in the "#sortabletablepending" "css_element"
    Then I should see "Pending"
    And I should see "Two"
    # Set tariff and click Single Semester approval
    When I set the field "eduScope (Tariff):" to "student"
    And I click on "button[value='limited']" "css_element"
    # Check redirection and removal from pending list
    Then I should see "Waiting for approval"
    And I should not see "userpending2" in the "#sortabletablepending" "css_element"
    # Check that userpending2 is in the approved list
    And I visit "/local/external_users/manage.php"
    When I press "Approved"
    Then I should see "userpending2" in the "#sortabletableapproved" "css_element"

  Scenario: Admin rejects a pending user by email notification only
    Given I log in as "admin"
    And I change window size to "large"
    And I visit "/local/external_users/manage.php"
    When I press "Waiting for approval"
    And I click on "userpending3" "link" in the "#sortabletablepending" "css_element"
    Then I should see "Pending"
    And I should see "Three"
    # Select Email only and comment
    When I click on "#option1" "css_element"
    And I set the field "comment" to "Incorrect documentation provided."
    And I click on ".bg-light-danger button.btn-danger" "css_element"
    # Check redirection and removal from pending list
    Then I should see "Waiting for approval"
    And I should not see "userpending3" in the "#sortabletablepending" "css_element"
    # Check that userpending3 is in the rejected list
    And I visit "/local/external_users/manage.php"
    When I press "Rejected"
    Then I should see "userpending3" in the "#sortabletablerejected" "css_element"

  Scenario: Admin rejects a pending user and deletes them
    Given I log in as "admin"
    And I change window size to "large"
    And I visit "/local/external_users/manage.php"
    When I press "Waiting for approval"
    And I click on "userpending4" "link" in the "#sortabletablepending" "css_element"
    Then I should see "Pending"
    And I should see "Four"
    # Select Email and delete
    When I click on "#option2" "css_element"
    And I set the field "comment" to "Document fake."
    And I click on ".bg-light-danger button.btn-danger" "css_element"
    # Check redirection
    Then I should see "Waiting for approval"
    And I should not see "userpending4" in the "#sortabletablepending" "css_element"
    # Verify they do not show up on Rejected page since they are deleted from DB
    And I visit "/local/external_users/manage.php"
    When I press "Rejected"
    Then I should not see "userpending4" in the "#sortabletablerejected" "css_element"

  Scenario: Admin revokes an approved user
    Given I log in as "admin"
    And I change window size to "large"
    And I visit "/local/external_users/manage.php"
    When I press "Approved"
    Then I should see "userapproved1" in the "#sortabletableapproved" "css_element"
    # Navigate to the user profile
    When I click on "userapproved1" "link" in the "#sortabletableapproved" "css_element"
    Then I should see "Approved"
    And I should see "One"
    # Revoke verification
    When I press "Revoke"
    # Check redirection and removal from approved list
    Then I should see "Approved"
    And I should not see "userapproved1" in the "#sortabletableapproved" "css_element"
