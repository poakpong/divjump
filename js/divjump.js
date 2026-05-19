(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * DivJump behavior
   * ย้าย div element ไปแทรกระหว่าง views-row ที่กำหนดจาก admin settings
   */
  Drupal.behaviors.divjump = {
    attach: function (context, settings) {

      // อ่านค่าจาก drupalSettings ที่ส่งมาจาก PHP
      const config = settings.divjump;
      if (!config || !config.divId || !config.rowPosition) {
        return;
      }

      const divId = config.divId;
      const rowPosition = parseInt(config.rowPosition, 10);

      // ใช้ once() เพื่อป้องกัน attach ซ้ำ (สำคัญสำหรับ AJAX)
      const container = once('divjump-moved', 'body', context);
      if (!container.length) {
        return;
      }

      const targetDiv = document.getElementById(divId);
      const rows = document.querySelectorAll('.views-row');

      if (!targetDiv) {
        if (typeof console !== 'undefined') {
          console.warn('[DivJump] ไม่พบ element ที่มี id: #' + divId);
        }
        return;
      }

      if (rows.length < rowPosition) {
        if (typeof console !== 'undefined') {
          console.warn('[DivJump] จำนวน .views-row (' + rows.length + ') น้อยกว่าตำแหน่งที่กำหนด (' + rowPosition + ')');
        }
        return;
      }

      // แทรก targetDiv ก่อน row ลำดับที่ rowPosition (index = rowPosition)
      // เช่น rowPosition = 3 → แทรกก่อน rows[3] = ระหว่าง row ที่ 3 และ 4
      rows[rowPosition].parentNode.insertBefore(targetDiv, rows[rowPosition]);

    }
  };

})(Drupal, drupalSettings);
