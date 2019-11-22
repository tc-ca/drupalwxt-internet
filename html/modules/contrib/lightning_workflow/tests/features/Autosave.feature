@lightning @lightning_workflow @api
Feature: Autosave for content editing forms
  As a content editor, I want my work to be backed up periodically
  So I can retrieve it if I accidentally lose it

  @javascript @with-module:autosave_form @af96a97b @orca_public
  Scenario: Retrieving autosaved work
    Given I am logged in as a user with the "access content overview, edit any moderated content, use editorial transition create_new_draft" permissions
    And moderated content:
      | title | moderation_state |
      | Test  | published        |
    When I visit "/admin/content"
    And I click "Test"
    And I visit the edit form
    # We need to wait for an initial autosave before making more changes.
    And I wait for my work to be autosaved
    And I enter "Testing" for "Title"
    And I wait for my work to be autosaved
    And I click "View"
    And I visit the edit form
    Then I should be able to restore my work
    And the "Title" field should contain "Testing"
