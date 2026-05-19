<?php

namespace Drupal\divjump\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings form สำหรับ DivJump module.
 */
class DivJumpSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['divjump.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'divjump_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('divjump.settings');

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--info">'
        . $this->t('<strong>DivJump</strong>: ย้าย div element ไปแทรกระหว่าง <code>.views-row</code> ที่กำหนด โดยใช้ JavaScript')
        . '</div>',
    ];

    $form['div_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Div ID ที่ต้องการย้าย'),
      '#description' => $this->t('กรอก ID ของ element ที่ต้องการย้าย <strong>ไม่ต้องใส่เครื่องหมาย #</strong><br>ตัวอย่าง: <code>block-thex-adsenseadunit-3</code>'),
      '#default_value' => $config->get('div_id'),
      '#required' => TRUE,
      '#placeholder' => 'block-thex-adsenseadunit-3',
      '#maxlength' => 255,
    ];

    $form['row_position'] = [
      '#type' => 'number',
      '#title' => $this->t('แทรกหลัง views-row ลำดับที่'),
      '#description' => $this->t('กรอกตัวเลขลำดับของ <code>.views-row</code> ที่ต้องการแทรกหลังจากนั้น<br>'
        . 'ตัวอย่าง: กรอก <strong>3</strong> = แทรกระหว่าง row ที่ 3 และ 4<br>'
        . '<em>เริ่มนับที่ 1</em>'),
      '#default_value' => $config->get('row_position') ?? 3,
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 100,
    ];

    $form['preview'] = [
      '#type' => 'details',
      '#title' => $this->t('ตัวอย่าง JavaScript ที่จะถูกใช้'),
      '#open' => FALSE,
    ];

    $div_id = $config->get('div_id') ?? 'YOUR_DIV_ID';
    $row_position = $config->get('row_position') ?? 3;

    $form['preview']['code'] = [
      '#type' => 'markup',
      '#markup' => '<pre><code>'
        . "const adBlock = document.getElementById('{$div_id}');\n"
        . "const rows = document.querySelectorAll('.views-row');\n"
        . "if (adBlock && rows.length >= {$row_position}) {\n"
        . "  rows[{$row_position}].parentNode.insertBefore(adBlock, rows[{$row_position}]);\n"
        . "}"
        . '</code></pre>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $div_id = trim($form_state->getValue('div_id'));

    // ห้ามใส่ # นำหน้า
    if (str_starts_with($div_id, '#')) {
      $form_state->setErrorByName('div_id', $this->t('ไม่ต้องใส่เครื่องหมาย # นำหน้า กรอกเฉพาะ ID เช่น block-thex-adsenseadunit-3'));
    }

    // ห้ามมี space
    if (str_contains($div_id, ' ')) {
      $form_state->setErrorByName('div_id', $this->t('Div ID ต้องไม่มีช่องว่าง'));
    }

    $row_position = (int) $form_state->getValue('row_position');
    if ($row_position < 1) {
      $form_state->setErrorByName('row_position', $this->t('ตำแหน่งต้องเป็นตัวเลขที่มากกว่า 0'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('divjump.settings')
      ->set('div_id', trim($form_state->getValue('div_id')))
      ->set('row_position', (int) $form_state->getValue('row_position'))
      ->save();

    parent::submitForm($form, $form_state);

    $this->messenger()->addMessage($this->t('บันทึกการตั้งค่า DivJump เรียบร้อยแล้ว กรุณา <a href="/admin/config/development/performance">clear cache</a> เพื่อให้การเปลี่ยนแปลงมีผล'));
  }

}
