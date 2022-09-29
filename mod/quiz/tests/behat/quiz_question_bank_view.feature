@mod @mod_quiz
Feature: As a teacher, I am able to view questions from question bank

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name   | intro                           | course | idnumber |
      | quiz       | Quiz 1 | Quiz 1 for testing the Add menu | C1     | quiz1    |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext    |
      | Test questions   | truefalse   | TF1   | First question  |
      | Test questions   | truefalse   | TF2   | Second question |
    And I am on the "Quiz 1" "mod_quiz > Edit" page logged in as "teacher1"

  @javascript
  Scenario: Add some new question to the quiz using '+ a new question' options of the 'Add' menu.
    When I open the "last" add to quiz menu
    And I follow "from question bank"
    And I should see "TF1"
    And I should see "TF2"
