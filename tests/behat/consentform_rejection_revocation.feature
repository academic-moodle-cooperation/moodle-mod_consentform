@mod @mod_consentform @amc @consentform_rejection
Feature: A teacher should be able to add the options of revocation and rejection to a consentform instance.
  In order to allow students to revoke or reject the agreement
  As a teacher
  I need to activate the revocation and/or rejection option within the instance settings.

  Background:
    Given the following config values are set as admin:
      | config           | value |
      | enablecompletion | 1     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: As a teacher I add a consentform with standard settings to the course. The option to revoke should be active.
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                         |consentform - Revoke   |
      | Consentform text to agree to | consentform text      |
      | Label Agreement Button       | I agree               |
      | Label Revocation Button      | I revoke my agreement |
    And I add a forum activity to course "Course 1" section "1" and I fill the form with:
      | Forum name | forum - Revoke |
    And I am on the "consentform - Revoke" "consentform activity" page
    And I follow "Define dependencies"
    Then I should see "forum - Revoke"
    And I click on "selectcoursemodule[]" "checkbox"
    And I am on the "consentform - Revoke" "consentform activity" page logged in as "student1"
    And I press "I agree"
    When I am on the "consentform - Revoke" "consentform activity" page logged in as "student1"
    And I press "I revoke my agreement"
    And I am on "Course 1" course homepage
    Then I should see "Not available unless:"

  @javascript
  Scenario: As a teacher I add a consentform with standard settings to the course. The option to refuse can be activated.
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | consentform - Refusal |
      | Consentform text to agree to | consentform text      |
      | Label Agreement Button       | I agree               |
      | Label Refusal Button         | I do not agree        |
    And I turn editing mode on
    And I am on the "consentform - Refusal" "consentform activity" page
    And I follow "Settings"
    And I set the field "optionrefuse" to "1"
    And I press "Save and return to course"
    And I am on "Course 1" course homepage
    And I add a forum activity to course "Course 1" section "1" and I fill the form with:
      | Forum name | forum - Refusal |
    And I am on the "consentform - Refusal" "consentform activity" page
    And I follow "Define dependencies"
    Then I should see "forum - Refusal"
    And I click on "selectcoursemodule[]" "checkbox"
    And I log in as "student1"
    And I am on the "consentform - Refusal" "consentform activity" page
    And I press "I do not agree"
    And I am on "Course 1" course homepage
    Then I should see "Not available unless:"
