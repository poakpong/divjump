(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * DivJump behavior.
   * Moves each configured div element and inserts it after the specified
   * .views-row index as defined in the admin settings.
   */
  Drupal.behaviors.divjump = {
    attach: function (context, settings) {

      var config = settings.divjump;
      if (!config || !config.divs || !config.divs.length) {
        return;
      }

      // Use once() to prevent re-processing on AJAX page updates.
      var container = once('divjump-moved', 'body', context);
      if (!container.length) {
        return;
      }

      var rows = document.querySelectorAll('.views-row');

      if (!rows.length) {
        if (typeof console !== 'undefined') {
          console.warn('[DivJump] No .views-row elements found on this page.');
        }
        return;
      }

      config.divs.forEach(function (item) {
        var divId       = item.div_id || '';
        var rowPosition = parseInt(item.row_position, 10) || 0;

        if (!divId || rowPosition < 1) {
          return;
        }

        var targetDiv = document.getElementById(divId);

        if (!targetDiv) {
          if (typeof console !== 'undefined') {
            console.warn('[DivJump] Element not found: #' + divId);
          }
          return;
        }

        if (rows.length < rowPosition) {
          if (typeof console !== 'undefined') {
            console.warn(
              '[DivJump] #' + divId + ': not enough .views-row elements (' +
              rows.length + ') for position ' + rowPosition + ' — skipping.'
            );
          }
          return;
        }

        // Insert targetDiv before rows[rowPosition].
        // rowPosition=3 → inserts before index 3, i.e. between row 3 and row 4.
        rows[rowPosition].parentNode.insertBefore(targetDiv, rows[rowPosition]);
      });

    }
  };

})(Drupal, drupalSettings);
