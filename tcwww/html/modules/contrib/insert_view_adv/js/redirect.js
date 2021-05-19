(function (Drupal) {

  'use strict';

  /**
   * Redirects after view reset.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block settings summaries.
   */
  Drupal.behaviors.insertViewAdvRedirect = {
    attach: function (context, settings) {
      if (typeof settings.insert_view_adv.reset_redirect !== 'undefined') {
        window.location.href = settings.insert_view_adv.reset_redirect;
      }
    }
  }

})(Drupal);
