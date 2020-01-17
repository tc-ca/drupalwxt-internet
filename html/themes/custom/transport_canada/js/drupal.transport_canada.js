/**
 * @file
 * Drupal OD Bootstrap object.
 */

/**
 * All Drupal OD Bootstrap JavaScript APIs are contained in this namespace.
 *
 * @namespace
 */
(function ($, Drupal) {
  'use strict';

  Drupal.od_bootstrap = {
    settings: drupalSettings.od_bootstrap || {},
  };

  /**
   * Returns the version of OD being used.
   *
   * @return {string}
   *   The version of OD being used.
   */
  Drupal.od_bootstrap.version = 'OD v4.0.25';

  var mediaQuery = ['xlargeview', 'largeview', 'mediumview'];
  Drupal.behaviors.WxTEqualizeHeight = {
    attach: function (context, settings) {
      var $equalHeight = $('.wxt-eqht');

      // Equal Height calculator
      var equalizeHeight = function (element) {
        $equalHeight.each(function () {
          var $this = $(this);
          var $thumbnail = $this.find('div > .thumbnail');
          var $child = $('div > .wxt-eqht-sel', this);
          var highestBox = 0;
          var highestselectBox = 0;

          $thumbnail
            .each(function () {
              var height = $(this).height();
              if (height > highestBox) {
                highestBox = height;
              }
            })
            .height(highestBox);

          $child
            .each(function () {
              var height = $(this).height();
              if (height > highestselectBox) {
                highestselectBox = height;
              }
            })
            .height(highestselectBox);
        });
      };

      // Initial page load
      checkSize();

      // Resize of the window
      $(window).resize(checkSize);

      // Trigger behavior based on CSS
      function checkSize() {
        for (var i = 0, len = mediaQuery.length; i < len; i++) {
          if ($('html').hasClass(mediaQuery[i]) === true) {
            var eqHght = equalizeHeight(this);
            if (eqHght) {
              Drupal.attachBehaviors(eqHght);
            }
          }
        }
      }
    },
  };

  Drupal.behaviors.WxTGeomap = {
    attach: function (context, settings) {
      $('.wb-geomap').on('wb-ready.wb-geomap', function (event, map) {
        $('.wb-geomap-layers > div').each(function (index, element) {
          var h3 = $(this).find('h3').text();
          $(this).find('h3').hide();
          $(this)
             .children('div')
             .wrap('<details></details>')
             .before('<summary><h2 class="h3">' + h3 + '</h2></summary>')
             .parent()
             .children('summary')
             .trigger('wb-init.wb-details');
        });
      });
    },
  };

})(window.jQuery, window.Drupal, window.drupalSettings);
