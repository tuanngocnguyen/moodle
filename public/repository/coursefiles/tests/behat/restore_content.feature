@repository @repository_coursefiles @javascript @_file_upload
Feature: Restoring a course keeps a legacy course file reference usable
  In order to restore a course with legacy course file references
  As a user
  I need to be able to restore the course and have the references remain usable

  Background:
    Given the following config values are set as admin:
      | legacyfilesinnewcourses | 1 |              |
      | legacyfilesaddallowed   | 1 |              |
      | legacyfiles             | 2 | moodlecourse |
    And the following "courses" exist:
      | fullname | shortname | legacyfiles |
      | Course 1 | C1        | 2           |
      | Course 2 | C2        | 2           |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | admin | C1     | editingteacher |
      | admin | C2     | editingteacher |

  @javascript
  Scenario: Restoring a course keeps a legacy course file reference usable
    Given the following "activities" exist:
      | activity | name         | intro        | introformat | course | idnumber |
      | folder   | Shared files | Shared files | 1           | C2     | folder1  |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And I log in as "admin"
    And I navigate to "Plugins > Repositories > Manage repositories" in site administration
    And I set the field "Action" in the "Legacy course files" "table_row" to "Enabled and visible"
    And I am on "Course 1" course homepage
    And I navigate to "Legacy course files" in current page administration
    And I press "Edit legacy course files"
    And I click on "Add..." "link"
    And I select "Upload a file" repository in file picker
    And I set the field "Attachment" to "#dirroot#/lib/tests/fixtures/empty.txt"
    And I press "Upload this file"
    And I press "Save changes"
    And I am on the "Shared files" "folder activity" page
    And I click on "Edit" "button"
    And I click on "Add..." "button"
    And I should see "Server files" in the ".fp-repo-area" "css_element"
    And I select "Server files" repository in file picker
    And I click on "System" "link" in the ".file-picker .fp-pathbar" "css_element"
    And I click on "Category 1" "folder" in repository content area
    And I click on "Course 1" "folder" in repository content area
    And I click on "Legacy course files" "folder" in repository content area
    And I click on "empty.txt" "file" in repository content area
    And I click on "Link to the file" "radio"
    And I click on "Select this file" "button"
    Then I should see "1" elements in "Files" filemanager
    And I should see "empty.txt" in the ".fp-content .fp-file.fp-isreference" "css_element"
    And I press "Save changes"
    When I backup "Course 2" course using this options:
      | Confirmation | Filename | alias_coursefiles_backup.mbz |
    And I go to the courses management page
    And I click on "delete" action for "Course 1" in management course listing
    And I click on "Delete" "button" in the "Confirm" "dialogue"
    And I press "Continue"
    And I am on the "Course 2" "restore" page
    And I restore "alias_coursefiles_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 restored |
    Then I should see "Shared files"
    And I click on "Shared files" "link" in the "region-main" "region"
    And I should see "empty.txt"
