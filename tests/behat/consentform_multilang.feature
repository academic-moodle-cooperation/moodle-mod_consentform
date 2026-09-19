@mod @mod_consentform @amc @javascript
Feature: Consentform button labels support the Multilang v2 filter
  In order to respond to consent forms in my current language
  As a student
  I need translated buttons on activity and course overview pages.

  Background:
    Given the "filter_multilang2" plugin is installed
    And the following config values are set as admin:
      | config           | value |
      | enablecompletion | 1     |
    And the "multilang2" filter is "on"
    And the "multilang2" filter applies to "content and headings"
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

  Scenario: Labels longer than 100 characters are filtered on the activity page
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                        | Multilang consent form |
      | Consentform text to agree to | Please confirm. |
      | Label Agreement Button      | {mlang de}Ich stimme dieser langen Einverstaendniserklaerung fuer den Multilang Test ausdruecklich zu{mlang}{mlang en}I agree to the terms & conditions{mlang} |
      | Label Refusal Button        | {mlang de}Ich lehne diese lange Einverstaendniserklaerung fuer den Multilang Test ausdruecklich ab{mlang}{mlang en}I refuse the terms & conditions{mlang} |
      | Label Revocation Button     | {mlang de}Ich widerrufe diese lange Einverstaendniserklaerung fuer den Multilang Test ausdruecklich{mlang}{mlang en}I revoke my agreement & consent{mlang} |
      | optionrefuse                | 1 |
      | optionrevoke                | 1 |
    When I am on the "Multilang consent form" "consentform activity" page logged in as "student1"
    Then "I agree to the terms & conditions" "button" should be visible
    And "I refuse the terms & conditions" "button" should be visible
    And "input[type='submit'][value*='{mlang']" "css_element" should not exist
    And I press "I refuse the terms & conditions"
    And I am on the "Multilang consent form" "consentform activity" page
    And "I refuse the terms & conditions" "button" should not exist
    And I press "I agree to the terms & conditions"
    And I am on the "Multilang consent form" "consentform activity" page
    And "I revoke my agreement & consent" "button" should be visible
    And "input[type='submit'][value*='{mlang']" "css_element" should not exist
    And I press "I revoke my agreement & consent"
    And I am on the "Multilang consent form" "consentform activity" page
    And "I agree to the terms & conditions" "button" should be visible
    And "I refuse the terms & conditions" "button" should be visible

  Scenario: Filtered buttons submit responses from the course overview
    Given I log in as "teacher1"
    And I add a consentform activity to course "Course 1" section "1" and I fill the form with:
      | Name                        | Multilang inline consent form |
      | Consentform text to agree to | Please confirm. |
      | Label Agreement Button      | {mlang de}Ich stimme dieser langen Einverstaendniserklaerung fuer den Multilang Test ausdruecklich zu{mlang}{mlang en}I agree to the terms & conditions{mlang} |
      | Label Refusal Button        | {mlang de}Ich lehne diese lange Einverstaendniserklaerung fuer den Multilang Test ausdruecklich ab{mlang}{mlang en}I refuse the terms & conditions{mlang} |
      | Label Revocation Button     | {mlang de}Ich widerrufe diese lange Einverstaendniserklaerung fuer den Multilang Test ausdruecklich{mlang}{mlang en}I revoke my agreement & consent{mlang} |
      | optionrefuse                | 1 |
      | optionrevoke                | 1 |
      | confirmincourseoverview      | 1 |
    When I am on the "Course 1" course page logged in as "student1"
    And I switch to "w-100" class iframe
    Then "I agree to the terms & conditions" "button" should be visible
    And "I refuse the terms & conditions" "button" should be visible
    And "input[type='submit'][value*='{mlang']" "css_element" should not exist
    And I press "I refuse the terms & conditions"
    And I switch to the main frame
    And I am on "Course 1" course homepage
    And I switch to "w-100" class iframe
    And "I refuse the terms & conditions" "button" should not exist
    And I press "I agree to the terms & conditions"
    And I switch to the main frame
    And I am on "Course 1" course homepage
    And I switch to "w-100" class iframe
    And "I revoke my agreement & consent" "button" should be visible
    And "input[type='submit'][value*='{mlang']" "css_element" should not exist
    And I press "I revoke my agreement & consent"
    And I switch to the main frame
    And I am on "Course 1" course homepage
    And I switch to "w-100" class iframe
    And "I agree to the terms & conditions" "button" should be visible
    And "I refuse the terms & conditions" "button" should be visible
