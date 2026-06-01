@mod @mod_consentform @amc @consentform_completion
Feature: A consentform can be completed when a student gives the required response.
  In order to control whether refusal blocks activity completion
  As a teacher
  I need explicit completion conditions for consentform responses.

  Background:
    Given the following config values are set as admin:
      | config           | value |
      | enablecompletion | 1     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 0        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: Completion is active by default and cannot be disabled.
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | consentform - Completion settings |
      | Consentform text to agree to | consentform text                  |
      | Label Agreement Button       | I agree                           |
    When I am on the "consentform - Completion settings" "consentform activity editing" page
    Then the "None" "field" should be disabled
    And the field "Add requirements" matches value "1"
    And the field "Agree to the consent form" matches value "1"

  @javascript
  Scenario: Agreeing to a consentform completes the activity by default.
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | consentform - Default agreement completion |
      | Consentform text to agree to | consentform text                           |
      | Label Agreement Button       | I agree                                    |
    Then "Student 1" user has not completed "consentform - Default agreement completion" activity
    When I am on the "consentform - Default agreement completion" "consentform activity" page logged in as "student1"
    And I press "I agree"
    And I am on the "Course 1" "course" page logged in as "teacher1"
    Then "Student 1" user has completed "consentform - Default agreement completion" activity

  @javascript
  Scenario: Refusing a consentform does not complete the activity by default.
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | consentform - Default refusal completion |
      | Consentform text to agree to | consentform text                         |
      | Label Agreement Button       | I agree                                  |
      | Label Refusal Button         | I do not agree                           |
      | optionrefuse                 | 1                                        |
    Then "Student 1" user has not completed "consentform - Default refusal completion" activity
    When I am on the "consentform - Default refusal completion" "consentform activity" page logged in as "student1"
    And I press "I do not agree"
    And I am on the "Course 1" "course" page logged in as "teacher1"
    Then "Student 1" user has not completed "consentform - Default refusal completion" activity

  @javascript
  Scenario: Manual completion can still be used after refusing a consentform.
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                                             | consentform - Manual completion |
      | Consentform text to agree to                     | consentform text                |
      | Label Agreement Button                           | I agree                         |
      | Label Refusal Button                             | I do not agree                  |
      | optionrefuse                                     | 1                               |
      | Students must manually mark the activity as done | 1                               |
    Then "Student 1" user has not completed "consentform - Manual completion" activity
    When I am on the "consentform - Manual completion" "consentform activity" page logged in as "student1"
    And I press "I do not agree"
    And the manual completion button of "consentform - Manual completion" is displayed as "Mark as done"
    And I toggle the manual completion state of "consentform - Manual completion"
    And the manual completion button of "consentform - Manual completion" is displayed as "Done"
    And I am on the "Course 1" "course" page logged in as "teacher1"
    Then "Student 1" user has completed "consentform - Manual completion" activity

  @javascript
  Scenario: Agreeing to a consentform can complete the activity when the response completion condition is enabled.
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                                | consentform - Agreement completion |
      | Consentform text to agree to        | consentform text                   |
      | Label Agreement Button              | I agree                            |
      | Add requirements                    | 1                                  |
      | Agree to or refuse the consent form | 1                                  |
    Then "Student 1" user has not completed "consentform - Agreement completion" activity
    When I am on the "consentform - Agreement completion" "consentform activity" page logged in as "student1"
    And I press "I agree"
    And I am on the "Course 1" "course" page logged in as "teacher1"
    Then "Student 1" user has completed "consentform - Agreement completion" activity

  @javascript
  Scenario: Refusing a consentform can complete the activity when the response completion condition is enabled.
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                                | consentform - Refusal completion |
      | Consentform text to agree to        | consentform text                 |
      | Label Agreement Button              | I agree                          |
      | Label Refusal Button                | I do not agree                   |
      | optionrefuse                        | 1                                |
      | Add requirements                    | 1                                |
      | Agree to or refuse the consent form | 1                                |
    Then "Student 1" user has not completed "consentform - Refusal completion" activity
    When I am on the "consentform - Refusal completion" "consentform activity" page logged in as "student1"
    And I press "I do not agree"
    And I am on the "Course 1" "course" page logged in as "teacher1"
    Then "Student 1" user has completed "consentform - Refusal completion" activity
