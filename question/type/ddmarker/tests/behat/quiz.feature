@qtype @qtype_ddmarker @_switch_window @_quiz_navigation
Feature: Quiz with drag-drop marker question
  As a student
  In order to check drag-drop marker questions if it works when background image is scaled or repositioned

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | user1     | Student1 | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And the following "questions" exist:
      | questioncategory | qtype     | name         | template | questiontext |
      | Test questions   | ddmarker  | Drag markers | mkmap    | DD Question  |
      | Test questions   | truefalse | TF1          |          | TF Question  |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | question      | page | maxmark |
      | Drag markers  | 1    | 1.0     |
      | TF1           | 2    | 1.0     |

  @javascript
  Scenario: Default screen size.
    Given I am on the "Test quiz" "mod_quiz > View" page logged in as "student1"
    And I press "Attempt quiz now"
    Then I should see "DD Question"
    And I drag "OU" to "322,213" in the drag and drop markers question
    And I drag "Railway station" to "144,84" in the drag and drop markers question
    And I drag "Railway station" to "195,180" in the drag and drop markers question
    And I drag "Railway station" to "267,302" in the drag and drop markers question
    And I press "Next page"
    Then I should see "TF Question"
    And I click on "True" "radio" in the "TF Question" "question"
    And I follow "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then the state of "DD Question" question is shown as "Correct"
    Then the state of "TF Question" question is shown as "Correct"

  @javascript
  Scenario: Small screen.
    Given I am on the "Test quiz" "mod_quiz > View" page logged in as "student1"
    And I press "Attempt quiz now"
    Then I should see "DD Question"
    And I change viewport size to "small"
    And I wait "1" seconds
    And I drag "OU" to "322,213" in the drag and drop markers question
    And I drag "Railway station" to "144,84" in the drag and drop markers question
    And I drag "Railway station" to "195,180" in the drag and drop markers question
    And I drag "Railway station" to "267,302" in the drag and drop markers question
    And I press "Next page"
    Then I should see "TF Question"
    And I click on "True" "radio" in the "TF Question" "question"
    And I follow "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then the state of "DD Question" question is shown as "Correct"
    Then the state of "TF Question" question is shown as "Correct"
#
  @javascript
  Scenario: Switching large/small windows on same page.
    Given I am on the "Test quiz" "mod_quiz > View" page logged in as "student1"
    And I press "Attempt quiz now"
    Then I should see "DD Question"
    And I change viewport size to "large"
    And I wait "1" seconds
    And I drag "OU" to "322,213" in the drag and drop markers question
    And I drag "Railway station" to "144,84" in the drag and drop markers question
    And I change viewport size to "small"
    And I wait "1" seconds
    And I drag "Railway station" to "195,180" in the drag and drop markers question
    And I drag "Railway station" to "267,302" in the drag and drop markers question
    And I press "Next page"
    Then I should see "TF Question"
    And I click on "True" "radio" in the "TF Question" "question"
    And I follow "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then the state of "DD Question" question is shown as "Correct"
    Then the state of "TF Question" question is shown as "Correct"

  @javascript
  Scenario: Navigate between pages, without changing windows size.
    Given I am on the "Test quiz" "mod_quiz > View" page logged in as "student1"
    And I press "Attempt quiz now"
    Then I should see "DD Question"
    And I drag "OU" to "322,213" in the drag and drop markers question
    And I drag "Railway station" to "144,84" in the drag and drop markers question
    And I press "Next page"
    And I press "Previous page"
    And I drag "Railway station" to "195,180" in the drag and drop markers question
    And I drag "Railway station" to "267,302" in the drag and drop markers question
    And I press "Next page"
    Then I should see "TF Question"
    And I click on "True" "radio" in the "TF Question" "question"
    And I follow "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then the state of "DD Question" question is shown as "Correct"
    Then the state of "TF Question" question is shown as "Correct"

  @javascript
  Scenario: Navigate between pages, and changing size.
    Given I am on the "Test quiz" "mod_quiz > View" page logged in as "student1"
    And I press "Attempt quiz now"
    Then I should see "DD Question"
    And I change viewport size to "large"
    And I drag "OU" to "322,213" in the drag and drop markers question
    And I drag "Railway station" to "144,84" in the drag and drop markers question
    And I press "Next page"
    And I change viewport size to "small"
    And I press "Previous page"
    And I wait "1" seconds
    And I drag "Railway station" to "195,180" in the drag and drop markers question
    And I drag "Railway station" to "267,302" in the drag and drop markers question
    And I press "Next page"
    Then I should see "TF Question"
    And I click on "True" "radio" in the "TF Question" "question"
    And I follow "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then the state of "DD Question" question is shown as "Correct"
    Then the state of "TF Question" question is shown as "Correct"

  @javascript
  Scenario: Closing left side navigation so that position of drop area changes,
    use large screen so there is no changes in ratio.
    Given I am on the "Test quiz" "mod_quiz > View" page logged in as "student1"
    And I change viewport size to "large"
    And I press "Attempt quiz now"
    Then I should see "DD Question"
    And I drag "OU" to "322,213" in the drag and drop markers question
    And I drag "Railway station" to "144,84" in the drag and drop markers question
    And I click on "[data-action=toggle-drawer]" "css_element"
    And I wait "3" seconds
    And I drag "Railway station" to "195,180" in the drag and drop markers question
    And I drag "Railway station" to "267,302" in the drag and drop markers question
    And I press "Next page"
    Then I should see "TF Question"
    And I click on "True" "radio" in the "TF Question" "question"
    And I follow "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then the state of "DD Question" question is shown as "Correct"
    Then the state of "TF Question" question is shown as "Correct"

  @javascript
  Scenario: Closing left side navigation so that position of drop area changes,
    use small screen so there is changes in ratio.
    Given I am on the "Test quiz" "mod_quiz > View" page logged in as "student1"
    And I press "Attempt quiz now"
    And I change viewport size to "small"
    Then I should see "DD Question"
    And I drag "OU" to "322,213" in the drag and drop markers question
    And I drag "Railway station" to "144,84" in the drag and drop markers question
    And I click on "[data-action=toggle-drawer]" "css_element"
    And I wait "3" seconds
    And I drag "Railway station" to "195,180" in the drag and drop markers question
    And I drag "Railway station" to "267,302" in the drag and drop markers question
    And I press "Next page"
    Then I should see "TF Question"
    And I click on "True" "radio" in the "TF Question" "question"
    And I follow "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then the state of "DD Question" question is shown as "Correct"
    Then the state of "TF Question" question is shown as "Correct"
