(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * DivJump behavior.
   *
   * Moves each configured div element and inserts it after the specified
   * .views-row index as defined in the admin settings.
   *
   * v1.1.3 fix:
   * - Replaced once(id, domElement) with a data-attribute guard.
   *   @drupal/once v1.0.1 expects a CSS selector string or Array/NodeList,
   *   not a bare DOM Element — passing one directly could return [] and
   *   cause the behavior to bail out silently.
   * - Added core/once to library dependencies (was missing).
   * - Library version bumped to 1.1.3 so browsers bust the old cached JS.
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

        // getElementById always searches the full document — safe regardless
        // of the current attach context (BigPipe fragment, AJAX, etc.).
        var targetDiv = document.getElementById(divId);
        if (!targetDiv) {
          if (typeof console !== 'undefined') {
            console.warn('[DivJump] Element not found: #' + divId);
          }
          return;
        }

        // Guard against double-processing with a data attribute.
        // More reliable than once(id, element) which requires a CSS selector
        // string or array in @drupal/once v1.0.1.
        if (targetDiv.hasAttribute('data-divjump-done')) {
          return;
        }
        targetDiv.setAttribute('data-divjump-done', '1');

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

        // rowPosition is 1-based.
        //   rowPosition = 3  →  insert before rows[3]  =  after the 3rd row.
        //   rowPosition >= rows.length  →  append after the last row.
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
          rows[rows.length - 1].parentNode.appendChild(targetDiv);
        }
        else {
          rows[rowPosition].parentNode.insertBefore(targetDiv, rows[rowPosition]);
        }
      });

    }
  };

})(Drupal, drupalSettings);
