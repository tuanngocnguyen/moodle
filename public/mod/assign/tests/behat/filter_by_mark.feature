@mod @mod_assign @filter_by_mark
Feature: In an assignment, teachers can filter submissions by mark
  In order to manage submissions more easily
  As a teacher
  I need to be able to filter submissions by mark range.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | student4 | Student   | 4        | student4@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | assignsubmission_onlinetext_enabled | 1                    |
      | submissiondrafts                    | 0                    |
      | grade[modgrade_type]                | point                |
      | grade[modgrade_point]               | 100                  |

  @javascript
  Scenario: Mark filter inputs become active only when their respective checkbox is ticked
    Given I am on the "Test assignment name" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I change window size to "large"
    And I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    # Inputs should be disabled by default.
    Then the "Greater than or equal" "field" should be disabled
    And the "Less than or equal" "field" should be disabled
    # Ticking the checkbox should enable the input.
    When I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    Then the "Greater than or equal" "field" should be enabled
    And the "Less than or equal" "field" should be disabled
    # Unticking the checkbox should disable the input.
    When I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    Then the "Greater than or equal" "field" should be disabled
    # Ticking less than checkbox should enable less than input.
    When I click on "Enable filter for mark less than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    Then the "Less than or equal" "field" should be enabled
    And the "Greater than or equal" "field" should be disabled

  @javascript
  Scenario: Teacher can filter submissions by mark range
    Given I am on the "Test assignment name" Activity page logged in as teacher1
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    And I change window size to "large"
    And I set the field "Grade out of 100" to "40"
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Test assignment name" Activity page
    And I go to "Student 2" "Test assignment name" activity advanced grading page
    And I set the field "Grade out of 100" to "75"
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Test assignment name" Activity page
    And I go to "Student 3" "Test assignment name" activity advanced grading page
    And I set the field "Grade out of 100" to "90"
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Test assignment name" Activity page
    And I navigate to "Submissions" in current page administration
    # All 4 students exist initially.
    And the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
      | Student 2 |
      | Student 3 |
      | Student 4 |
    # Filter for mark greater than or equal to 70.
    And I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    And I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I set the field "Greater than or equal" to "70"
    When I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 2 |
      | Student 3 |
    And the following should not exist in the "submissions" table:
      | -2-       |
      | Student 1 |
      | Student 4 |
    And ".badge" "css_element" should exist in the ".extrafilters .dropdown-toggle" "css_element"
    And I should see "+1" in the ".extrafilters .badge" "css_element"
    # Also add less than or equal 85 filter.
    And I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    And I click on "Enable filter for mark less than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I set the field "Less than or equal" to "85"
    When I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 2 |
    And the following should not exist in the "submissions" table:
      | -2-       |
      | Student 1 |
      | Student 3 |
      | Student 4 |
    And I should see "+2" in the ".extrafilters .badge" "css_element"
    # Filter by less than or equal only: Student 1 (40) is shown, but Student 4 (ungraded) is excluded.
    And I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    And I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I set the field "Less than or equal" to "50"
    When I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
    And the following should not exist in the "submissions" table:
      | -2-       |
      | Student 2 |
      | Student 3 |
      | Student 4 |
    And I should see "+1" in the ".extrafilters .badge" "css_element"
    # Reset filters.
    When I click on "Clear all" "link" in the ".tertiary-navigation" "css_element"
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
      | Student 2 |
      | Student 3 |
      | Student 4 |

  @javascript
  Scenario: Teacher can filter submissions by scale grade
    Given the following "scales" exist:
      | name        | scale                 |
      | Test scale  | Fail,Pass,Distinction |
    And the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Scale assignment     |
      | assignsubmission_onlinetext_enabled | 1                    |
      | submissiondrafts                    | 0                    |
    And I am on the "Scale assignment" "assign activity editing" page logged in as teacher1
    And I change window size to "large"
    And I set the following fields to these values:
      | grade[modgrade_type]  | Scale      |
      | grade[modgrade_scale] | Test scale |
    And I press "Save and return to course"
    And I am on the "Scale assignment" Activity page
    And I go to "Student 1" "Scale assignment" activity advanced grading page
    And I set the field "Grade" to "Fail"
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Scale assignment" Activity page
    And I go to "Student 2" "Scale assignment" activity advanced grading page
    And I set the field "Grade" to "Pass"
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Scale assignment" Activity page
    And I go to "Student 3" "Scale assignment" activity advanced grading page
    And I set the field "Grade" to "Distinction"
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Scale assignment" Activity page
    And I navigate to "Submissions" in current page administration
    And the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
      | Student 2 |
      | Student 3 |
      | Student 4 |
    # Filter for scale greater than or equal to Pass.
    When I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    And I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I set the field "Greater than or equal" to "Pass"
    And I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 2 |
      | Student 3 |
    And the following should not exist in the "submissions" table:
      | -2-       |
      | Student 1 |
      | Student 4 |
    # Filter for scale less than or equal to Fail: Student 1 (Fail) is shown, Student 4 (ungraded) is excluded.
    When I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    And I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I click on "Enable filter for mark less than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I set the field "Less than or equal" to "Fail"
    And I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
    And the following should not exist in the "submissions" table:
      | -2-       |
      | Student 2 |
      | Student 3 |
      | Student 4 |
    # Navigate to point assignment: scale filter should not carry over to point assignment.
    When I am on the "Test assignment name" Activity page
    And I navigate to "Submissions" in current page administration
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
      | Student 2 |
      | Student 3 |
      | Student 4 |
    And ".badge" "css_element" should not exist in the ".extrafilters .dropdown-toggle" "css_element"
    When I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    Then the "Greater than or equal" "field" should be disabled
    And the "Less than or equal" "field" should be disabled

  @javascript
  Scenario: Mark filter range validation prevents submitting an invalid range
    Given I am on the "Test assignment name" Activity page logged in as teacher1
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    And I change window size to "large"
    And I set the field "Grade out of 100" to "40"
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Test assignment name" Activity page
    And I go to "Student 3" "Test assignment name" activity advanced grading page
    And I set the field "Grade out of 100" to "90"
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Test assignment name" Activity page
    And I navigate to "Submissions" in current page administration
    And I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    # Setting >= 80 and < 50 is an invalid range and should block submission.
    And I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I set the field "Greater than or equal" to "80"
    And I click on "Enable filter for mark less than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I set the field "Less than or equal" to "50"
    When I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
      | Student 3 |
    # Unticking the lower bound should allow submission of the less than filter.
    When I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
    And the following should not exist in the "submissions" table:
      | -2-       |
      | Student 3 |
    # Ticking the lower bound again should restore the invalid range and block submission.
    When I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    And I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I set the field "Greater than or equal" to "80"
    And I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    # Submission was blocked, so the filtered table remains unchanged.
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
    And the following should not exist in the "submissions" table:
      | -2-       |
      | Student 3 |
    # Unticking the upper bound should allow submission of the greater than or equal filter.
    When I click on "Enable filter for mark less than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 3 |
    And the following should not exist in the "submissions" table:
      | -2-       |
      | Student 1 |

  @javascript
  Scenario: Mark filter inputs have a maximum value corresponding to the assignment maximum mark
    Given I am on the "Test assignment name" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I change window size to "large"
    And I click on "Advanced" "button" in the ".tertiary-navigation" "css_element"
    Then the "max" attribute of "Greater than or equal" "field" should contain "100"
    And the "max" attribute of "Less than or equal" "field" should contain "100"
    When I click on "Enable filter for mark greater than or equal to" "checkbox" in the ".extrafilters .dropdown-menu" "css_element"
    And I set the field "Greater than or equal" to "101"
    And I click on "Apply" "button" in the ".extrafilters .dropdown-menu" "css_element"
    # All students exist initially and form was not submitted.
    Then the following should exist in the "submissions" table:
      | -2-       |
      | Student 1 |
      | Student 2 |
      | Student 3 |
      | Student 4 |
