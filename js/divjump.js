(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * DivJump behavior
   * ย้าย div elements หลายรายการไปแทรกระหว่าง views-row ตาม config ที่ตั้งไว้
   */
  Drupal.behaviors.divjump = {
    attach: function (context, settings) {

      // อ่านค่าจาก drupalSettings
      var config = settings.divjump;
      if (!config || !config.divs || !config.divs.length) {
        return;
      }

      // ใช้ once() ป้องกัน attach ซ้ำ (สำคัญสำหรับ AJAX pages)
      var container = once('divjump-moved', 'body', context);
      if (!container.length) {
        return;
      }

      // โหลด .views-row ทั้งหมดในหน้า (ทำครั้งเดียว)
      var rows = document.querySelectorAll('.views-row');

      if (!rows.length) {
        if (typeof console !== 'undefined') {
          console.warn('[DivJump] ไม่พบ .views-row ในหน้านี้');
        }
        return;
      }

      // วน loop แต่ละ Div ที่ต้องการย้าย
      config.divs.forEach(function (item) {
        var divId       = item.div_id       || '';
        var rowPosition = parseInt(item.row_position, 10) || 0;

        if (!divId || rowPosition < 1) {
          return;
        }

        var targetDiv = document.getElementById(divId);

        if (!targetDiv) {
          if (typeof console !== 'undefined') {
            console.warn('[DivJump] ไม่พบ element ที่มี id: #' + divId);
          }
          return;
        }

        if (rows.length < rowPosition) {
          if (typeof console !== 'undefined') {
            console.warn(
              '[DivJump] #' + divId + ': จำนวน .views-row (' + rows.length +
              ') น้อยกว่าตำแหน่งที่กำหนด (' + rowPosition + ') — ข้ามรายการนี้'
            );
          }
          return;
        }

        // แทรก targetDiv ก่อน rows[rowPosition]
        // rowPosition=3 → แทรกก่อน index 3 = ระหว่าง row 3 และ 4 (นับจาก 1)
        rows[rowPosition].parentNode.insertBefore(targetDiv, rows[rowPosition]);
      });

    }
  };

})(Drupal, drupalSettings);
