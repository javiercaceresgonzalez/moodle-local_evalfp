@local @local_evalfp
Feature: Control access to EvalFP course pages
  In order to protect course assessment configuration
  As a course user
  I need EvalFP management pages to respect course capabilities

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry     | Teacher  | teacher1@example.com |
      | user1    | Ursula    | User     | user1@example.com    |
    And the following "courses" exist:
      | fullname      | shortname |
      | EvalFP course | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | user1    | C1     | student        |

  Scenario: Only users with the EvalFP course capability can access the plugin
    Given I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "EvalFP"
    And I log out
    When I am on the "C1" "Course" page logged in as "user1"
    Then I should not see "EvalFP"
