(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.TaxonomyManagerTermData = function (tid, tree) {
    // We change the hidden form element which then triggers the AJAX system.
    window.location.href = '/taxonomy/term/' + tid + '/edit' + '?destination=' + window.location.pathname;
  };

})(jQuery, Drupal, drupalSettings);
