@lightning @api @lightning_workflow
Feature: Integration of workflows with Quick Edit

  @f2beeeda @javascript @with-module:quickedit @orca_public
  Scenario: Quick Edit should be available for unpublished content
    Given I am logged in as a user with the "access content overview, access in-place editing, access contextual links, use editorial transition create_new_draft, view any unpublished content, edit any moderated content" permissions
    And moderated content:
      | title  | moderation_state |
      | Foobar | draft            |
    When I visit "/admin/content"
    And I click "Foobar"
    Then Quick Edit should be enabled
