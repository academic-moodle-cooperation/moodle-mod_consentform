@mod @mod_consentform @amc
Feature: In course, a teacher should be able to add a new consentform module
    In order to add a new consentform
    As a teacher
    I need to be able to add a new consentform and save it correctly.

  @javascript
  Scenario: Add a consentform instance
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