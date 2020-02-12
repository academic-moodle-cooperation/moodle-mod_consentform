@mod @mod_consentform @amc
Feature: In a course, a teacher should be able to add the control of an activity to this consentform module
    In order to add an activity control
    As a teacher
    I need to be able to add the activity to this consentform instance.

  @javascript
  Scenario: Add a consentform and a quiz instance to the course
    Given the following config values are set as admin:
      | config           | value |
      | enablecompletion | 1     |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | completion |
      | Course 1 | C1        | 0        | 0         | 2          |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@teacher.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "Consentform" to section "1" and I fill the form with:
      | Name                                      | My Consentform  |
      | Module description                        | my co                          |
      | Consentform text to agree/disagree to     | Text.....                      |
    And I follow "My Consentform"
    Then I should see "My Consentform"
    When I am on "Course 1" course homepage
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Test quiz name        |
      | Description | Test quiz description |
    And I add a "True/False" question to the "Test quiz name" quiz with:
      | Question name                      | First question                          |
      | Question text                      | Answer the first question               |
      | General feedback                   | Thank you, this is the general feedback |
      | Correct answer                     | False                                   |
      | Feedback for the response 'True'.  | So you think it is true                 |
      | Feedback for the response 'False'. | So you think it is false                |
    And I follow "Test quiz name"
    Then I should see "Test quiz name"
    When I am on "Course 1" course homepage
    And I follow "My Consentform"
    And I click on "selectcoursemodule[]" "checkbox"
    When I am on "Course 1" course homepage
    Then I should see "Not available unless: The activity"
