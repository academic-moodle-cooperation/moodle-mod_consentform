@mod @mod_confidential @amc
Feature: In course, a teacher should be able to add a new confidential module
    In order to add a new confidential
    As a teacher
    I need to be able to add a new confidential and save it correctly.

  @javascript
  Scenario: Add a confidential instance
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@teacher.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "Confidentiality obligation" to section "1" and I fill the form with:
      | Name                                      | My Confidentiality Obligation  |
      | Module description                        | my co                          |
      | Confidentiality text to agree/disagree to | Text.....                      |
    And I follow "My Confidentiality Obligation"
    Then I should see "My Confidentiality Obligation"