@core @core_question @qbank_filter
Feature: A teacher can pagimate through question bank questions
  In order to paginate questions
  As a teacher
  I must be able to paginate

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | weeks |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | questioncategory | name           |
      | Course       | C1        | Top              | Used category  |
    Given 100 "questions" exist with the following data:
      | questioncategory | Used category                 |
      | qtype            | essay                         |
      | name             | Tests question [count]        |
      | questiontext     | Write about whatever you want |
    And the following "questions" exist:
      | questioncategory | qtype | name                  | questiontext                  |
      | Used category    | essay | Not on first page     | Write about whatever you want |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage

  @javascript
  Scenario: Questions can be paginated
    When I navigate to "Question bank" in current page administration
    And I set the field "Type or select..." in the "Filter 1" "fieldset" to "Course 1"
    And I click on "Apply filters" "button"
    And I should see "Tests question 1"
    And I should not see "Not on first page"
    And I click on "2" "link" in the ".pagination" "css_element"
    And I should see "Not on first page"
