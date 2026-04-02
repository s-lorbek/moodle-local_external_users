@local @local_external_users @javascript @_file_upload
Feature: Allow browsing
  In order to allow some external users to browse
  As an admin
  I need to set browsing permissions

  Background:
    Given the following config values are set as admin:
      | passwordpolicy  | 0         |
      | registerauth    | external  |
      | auth            | external  |
      | allowbrowsing   | true      |
      | allowbrowsing_tariff | external |
    And the following "users" exist:
      | username | firstname | lastname | email             | auth   | suspended | timecreated        | profile_field_external_user |
      | extuser | Verified      | 1     | user1@example.com    | external | 0         | ## 201 days ago ## | 1                      |
    And  I set the following custom profile field values:
      | username    | external_user_verified  |
      | extuser     | 0                       |
  Scenario: A verified external user can browse
    Given I log in as "extuser"
    And I change window size to "large"
    Then I should see "You must first be verified"

    And I upload "local/external_users/tests/fixtures/doc.pdf" file to "Study confirmation or certificate of completion" filemanager
    And I wait "3" seconds

    And I upload "local/external_users/tests/fixtures/pic.jpg" file to "Photo/scan of your ID document" filemanager

    And I press "Submit"

