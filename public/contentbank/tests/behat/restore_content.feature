@core @core_contentbank @core_h5p @contentbank_h5p
Feature: Content bank contents are retained when course is restored
  In order to restore content bank contents
  As a manager
  I need to be able to restore course containing the content bank

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | admin | C1     | editingteacher |
    And the following "contentbank content" exist:
      | contextlevel | reference | contenttype     | user  | contentname       | filepath                              |
      | Course       | C1        | contenttype_h5p | admin | filltheblanks.h5p | /h5p/tests/fixtures/filltheblanks.h5p |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Deleted courses with content banks can be restored
    Given I navigate to "Courses > Manage courses and categories" in site administration
    And I click on "delete" action for "Course 1" in management course listing
    And I click on "Delete" "button" in the "Confirm" "dialogue"
    And I press "Continue"
    And I navigate to "Recycle bin" in current page administration
    And I click on "Restore" "link" in the "Course 1" "table_row"
    And I am on "Course 1" course homepage
    When I navigate to "Content bank" in current page administration
    And I click on "filltheblanks.h5p" "link"
    And I wait until "h5p-player" iframe is interactable and switch to it
    And I wait until "h5p-iframe" iframe is interactable and switch to it
    Then I should see "Of which countries are Berlin, Washington, Beijing, Canberra and Brasilia the capitals?"

  @javascript
  Scenario: Restoring a course keeps a cross-course content bank file reference usable
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 2 | C2        |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | admin | C2     | editingteacher |
    And the following "activities" exist:
      | activity | name         | intro        | introformat | course | idnumber |
      | folder   | Shared files | Shared files | 1           | C2     | folder1  |
    And I am on the Folder "Shared files" page
    And I click on "Edit" "button"
    And I click on "Add..." "button"
    And I should see "Content bank" in the ".fp-repo-area" "css_element"
    And I select "Content bank" repository in file picker
    And I click on "Course 1" "folder" in repository content area
    And I click on "filltheblanks.h5p" "file" in repository content area
    And I click on "Select this file" "button"
    Then I should see "1" elements in "Files" filemanager
    And I should see "filltheblanks.h5p" in the ".fp-content .fp-file" "css_element"
    When I backup "Course 2" course using this options:
      | Confirmation | Filename | alias_contentbank_backup.mbz |
    And I restore "alias_contentbank_backup.mbz" backup into a new course using this options:
      | Initial | Include content bank content | 1 |
    Then I should see "Shared files"
    And I should see "filltheblanks.h5p"
