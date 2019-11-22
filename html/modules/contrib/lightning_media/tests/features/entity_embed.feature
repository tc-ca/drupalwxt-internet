@lightning @api @lightning_media @javascript
Feature: Embedding entities in a WYSIWYG editor

  Background:
    Given I am logged in as a media_creator

  @917d6aa6
  Scenario Outline: Embed code-based media types use the Embedded display plugin by default
    Given <bundle> media from embed code:
      """
      <embed_code>
      """
    When I visit "/node/add/page"
    And I open the media browser
    And I select item 1
    And I submit the entity browser
    Then I should be able to embed the media item

    Examples:
      | bundle    | embed_code                                                   |
      | video     | https://www.youtube.com/watch?v=N2_HkWs7OM0                  |
      | video     | https://vimeo.com/25585320                                   |
      | tweet     | https://twitter.com/djphenaproxima/status/879739227617079296 |
      | instagram | https://www.instagram.com/p/lV3WqOoNDD                       |

  @cd742161
  Scenario: Embedding an image with embed-specific alt text and image style
    Given a random image
    When I visit "/node/add/page"
    And I enter "Foobar" for "Title"
    And I open the media browser
    And I select item 1
    And I submit the entity browser
    And I embed the media item with options:
      | Image style | Alternate text                | Title    |
      | medium      | Behold my image of randomness | Ye gods! |
    And I press "Save"
    Then the response should contain "Behold my image of randomness"
    And the response should contain "Ye gods!"
