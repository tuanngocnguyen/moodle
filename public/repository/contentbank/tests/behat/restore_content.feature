@repository @repository_contentbank @javascript @core_h5p
Feature: Restoring a course keeps a cross-course content bank file reference usable
  In order to restore a course with cross-course content bank file references
  As a user
  I need to be able to restore the course and have the references remain usable

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
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And I am on the "Shared files" "folder activity" page logged in as admin
    And I click on "Edit" "button"
    And I click on "Add..." "button"
    And I should see "Content bank" in the ".fp-repo-area" "css_element"
    And I select "Content bank" repository in file picker
    And I click on "System" "link" in the ".file-picker .fp-pathbar" "css_element"
    And I click on "Category 1" "folder" in repository content area
    And I click on "Course 1" "folder" in repository content area
    And I click on "filltheblanks.h5p" "file" in repository content area
    And I click on "Select this file" "button"
    Then I should see "1" elements in "Files" filemanager
    And I should see "filltheblanks.h5p" in the ".fp-content .fp-file" "css_element"
    And I press "Save changes"
    When I backup "Course 2" course using this options:
      | Confirmation | Filename | alias_contentbank_backup.mbz |
    And I restore "alias_contentbank_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 restored |
    Then I should see "Shared files"
    And I click on "Shared files" "link" in the "region-main" "region"
    And I should see "filltheblanks.h5p"
