@mod @mod_consentform @adminsetting @amc
Feature: The usability of the plugin consentform depends on the admin setting "enable completion".
  In order to utilize a consentform in a course
  As an admin
  I need to activate the admin setting "enable completion" tracking.

  Background:
    Given the following config values are set as admin:
      | config           | value |
      | enablecompletion | 1     |
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: I switch as an admin to the site administration and change the value of "enable completion tracking" to "No". Then I login as a teacher and add a new consentform to the course and I check whether it is displayed correctly.
    Given I log in as "admin"
    And I navigate to "General > Advanced features" in site administration
    And I set the field "Enable completion tracking" to "0"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Consentform" to section "1" and I fill the form with:
      | Name | consentform name - No |
      | Consentform text to agree to | consentform text |
      | Label Agreement Button | I agree |
    When I am on the "consentform name - No" "consentform activity" page logged in as student1
    Then I should see "Completion not active:"

  @javascript
  Scenario: I switch as an admin to the site administration and change the value of "enable completion tracking" to "Yes". Then I login as a teacher and add a new consentform to the course and I check whether it is displayed correctly.
    Given I log in as "admin"
    And I navigate to "General > Advanced features" in site administration
    And I set the field "Enable completion tracking" to "1"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Consentform" to section "1" and I fill the form with:
      | Name | consentform name - Yes |
      | Consentform text to agree to | consentform text |
      | Label Agreement Button | I agree |
    When I am on the "consentform name - Yes" "consentform activity" page logged in as student1
    Then I should see "consentform text"
