@mod @mod_consentform @addconsentform
Feature: In a course, a teacher should be able to add a new consentform
    In order to add a new consentform
    As a teacher
    I need to be able to add a new consentform and save it.

  @javascript
  Scenario: Add a consentform instance
    Given the following config values are set as admin:
      | config           | value |
      | enablecompletion | 1     |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 0         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@teacher.com |
      | student1 | Student   | 1        | student1@students.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "consentform" to section "1" and I fill the form with:
      | Name | consentform name      |
      | Consentform text to agree to | Add a consentform to the current course (Description) |
    And I log out
    And I am on the "consentform name" "consentform activity" page logged in as student1
    Then I should see "Add a consentform to the current course (Description)"