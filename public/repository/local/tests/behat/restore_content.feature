@repository @repository_local @javascript @_file_upload
Feature: Restoring a course keeps a server file reference usable
  In order to restore a course with cross-course server file references
  As a user
  I need to be able to restore the course and have the references remain usable

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | admin | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name      | intro     | introformat | course | idnumber |
      | folder   | Folder C1 | C1 folder | 1           | C1     | folderC1 |

  @javascript
  Scenario: Restoring a course keeps a cross-course server file reference usable
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 2 | C2        |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | admin | C2     | editingteacher |
    And the following "activities" exist:
      | activity | name         | intro        | introformat | course | idnumber |
      | folder   | Shared files | Shared files | 1           | C2     | folder1  |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And I am on the "Folder C1" "folder activity" page logged in as admin
    And I click on "Edit" "button"
    And I upload "lib/tests/fixtures/empty.txt" file to "Files" filemanager
    And I press "Save changes"
    And I am on the "Shared files" "folder activity" page
    And I click on "Edit" "button"
    And I click on "Add..." "button"
    And I should see "Server files" in the ".fp-repo-area" "css_element"
    And I select "Server files" repository in file picker
    And I click on "System" "link" in the ".file-picker .fp-pathbar" "css_element"
    And I click on "Category 1" "folder" in repository content area
    And I click on "Course 1" "folder" in repository content area
    And I click on "Folder C1 (Folder)" "folder" in repository content area
    And I click on "empty.txt" "file" in repository content area
    And I click on "Link to the file" "radio"
    And I click on "Select this file" "button"
    Then I should see "1" elements in "Files" filemanager
    And I should see "empty.txt" in the ".fp-content .fp-file.fp-isreference" "css_element"
    And I press "Save changes"
    When I backup "Course 2" course using this options:
      | Confirmation | Filename | alias_local_backup.mbz |
    And I go to the courses management page
    And I click on "delete" action for "Course 1" in management course listing
    And I click on "Delete" "button" in the "Confirm" "dialogue"
    And I press "Continue"
    And I am on the "Course 2" "restore" page
    And I restore "alias_local_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 restored |
    Then I should see "Shared files"
    And I click on "Shared files" "link" in the "region-main" "region"
    And I should see "empty.txt"
