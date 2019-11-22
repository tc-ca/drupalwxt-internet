@lightning @lightning_workflow @api
Feature: A sidebar for moderating content

  @1d83813d @javascript @with-module:toolbar @with-module:moderation_sidebar
  Scenario: Moderating content using the sidebar
    Given I am logged in as a user with the "access content overview, access toolbar, use moderation sidebar, view any unpublished content, edit any moderated content, use editorial transition review, use editorial transition publish" permissions
    And moderated content:
      | title | moderation_state |
      | Test  | draft            |
    When I visit "/admin/content"
    And I click "Test"
    And I open the moderation sidebar
    And I press the "Publish" button
    Then I should see "The moderation state has been updated."
    And the current moderation state should be "Published"
