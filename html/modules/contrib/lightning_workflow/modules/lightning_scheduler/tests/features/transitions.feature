@api @lightning @lightning_workflow @lightning_scheduler
Feature: Scheduling transitions on content

  @368f0045 @javascript
  Scenario: Automatically publishing, then unpublishing, in the future
    Given I am logged in as a user with the "create moderated content, view own unpublished content, edit own moderated content, use editorial transition create_new_draft, use editorial transition review, use editorial transition publish, use editorial transition archive, schedule editorial transition publish, schedule editorial transition archive, view latest version, administer nodes" permissions
    When I visit "/node/add/moderated"
    And I enter "Schedule This" for "Title"
    And I schedule a transition to Published in 10 seconds
    And I schedule a transition to Archived in 20 seconds
    And I press "Save"
    And I run cron after 12 seconds
    And I run cron after 22 seconds
    And I visit the edit form
    Then I should see "Current state Archived"
    And I should not see a ".scheduled-transition" element
