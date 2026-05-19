(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * DivJump behavior.
   *
   * Moves each configured div element and inserts it after the specified
   * .views-row index as defined in the admin settings.
   *
   * Bug fix (v1.1.2):
   * - once() is now applied per target element instead of on <body>.
   *   Previously, once('divjump-moved', 'body', context) would return empty
   *   when Drupal's BigPipe / AJAX called attach() with a fragment context
   *   that did not contain <body>, causing the entire behavior to be skipped.
   * - Added optional views_selector to scope .views-row queries to a specific
   *   container, preventing mismatches when multiple views exist on a page.
   * - Handles edge case where rowPosition equals the total row count
   *   (appends after the last row instead of crashing).
   */
  Drupal.behaviors.divjump = {
    attach: function (context, settings) {

      var config = settings.divjump;
      if (!config || !config.divs || !config.divs.length) {
        return;
      }

      config.divs.forEach(function (item) {
        var divId         = item.div_id       || '';
        var rowPosition   = parseInt(item.row_position, 10) || 0;
        var viewsSelector = item.views_selector || '';

        if (!divId || rowPosition < 1) {
          return;
        }

        // Search the full document — getElementById always does this anyway.
        var targetDiv = document.getElementById(divId);
        if (!targetDiv) {
          if (typeof console !== 'undefined') {
            console.warn('[DivJump] Element not found: #' + divId);
          }
          return;
        }

        // Guard against double-processing by marking the target element itself.
        // This is safe across BigPipe / AJAX re-attaches because we check the
        // actual element, not the attach context.
        var processed = once('divjump-' + divId, targetDiv);
        if (!processed.length) {
          return; // Already moved in a previous attach cycle.
        }

        // Determine the scope for .views-row queries.
        var scope = document;
        if (viewsSelector) {
          scope = document.querySelector(viewsSelector);
          if (!scope) {
            if (typeof console !== 'undefined') {
              console.warn('[DivJump] Views container not found: ' + viewsSelector);
            }
            return;
          }
        }

        var rows = scope.querySelectorAll('.views-row');

        if (!rows.length) {
          if (typeof console !== 'undefined') {
            console.warn('[DivJump] No .views-row elements found'
              + (viewsSelector ? ' inside "' + viewsSelector + '"' : ' on this page') + '.');
          }
          return;
        }

        // rowPosition is 1-based:
        //   rowPosition = 3  →  insert before rows[3]  =  after the 3rd row
        //   rowPosition = 10 with 10 rows  →  append after the last row
        if (rowPosition > rows.length) {
          if (typeof console !== 'undefined') {
            console.warn(
              '[DivJump] #' + divId + ': rowPosition (' + rowPosition +
              ') exceeds row count (' + rows.length + '). Appending after last row.'
            );
          }
          rows[rows.length - 1].parentNode.appendChild(targetDiv);
          return;
        }

        if (rowPosition === rows.length) {
          // Insert after the very last row.
          rows[rows.length - 1].parentNode.appendChild(targetDiv);
        }
        else {
          // Insert before rows[rowPosition]  (0-indexed → between row N and N+1).
          rows[rowPosition].parentNode.insertBefore(targetDiv, rows[rowPosition]);
        }
      });

    }
  };

})(Drupal, drupalSettings);
