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

    // โหลด divs จาก form state (ระหว่าง AJAX) หรือจาก config (ครั้งแรก)
    if ($form_state->get('divs') === NULL) {
      $saved = $config->get('divs') ?? [];
      $form_state->set('divs', !empty($saved) ? $saved : [['div_id' => '', 'row_position' => 3]]);
    }
    $divs = $form_state->get('divs');

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--info">'
        . $this->t('<strong>DivJump</strong>: ย้าย div element ไปแทรกระหว่าง <code>.views-row</code> ที่กำหนด โดยใช้ JavaScript')
        . '</div>',
    ];

    // ─── ตารางรายการ Div ────────────────────────────────────────────────────
    $form['divs_wrapper'] = [
      '#type'   => 'fieldset',
      '#title'  => $this->t('รายการ Div ที่ต้องการย้าย'),
      '#prefix' => '<div id="divjump-divs-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['divs_wrapper']['divs'] = [
      '#type'   => 'table',
      '#header' => [
        $this->t('Div ID (ไม่ต้องใส่ #)'),
        $this->t('แทรกหลัง Row ที่'),
        $this->t(''),
      ],
      '#empty'  => $this->t('ยังไม่มีรายการ กด "เพิ่ม Div" เพื่อเพิ่มรายการ'),
    ];

    foreach ($divs as $i => $div) {
      $form['divs_wrapper']['divs'][$i]['div_id'] = [
        '#type'          => 'textfield',
        '#default_value' => $div['div_id'] ?? '',
        '#placeholder'   => 'block-thex-adsenseadunit-3',
        '#maxlength'     => 255,
        '#attributes'    => ['style' => 'min-width:300px'],
      ];

      $form['divs_wrapper']['divs'][$i]['row_position'] = [
        '#type'          => 'number',
        '#default_value' => $div['row_position'] ?? 3,
        '#min'           => 1,
        '#max'           => 999,
        '#size'          => 6,
        '#description'   => $this->t('แทรกหลัง row ที่ n (นับจาก 1)'),
      ];

      $form['divs_wrapper']['divs'][$i]['remove'] = [
        '#type'                    => 'submit',
        '#value'                   => $this->t('ลบ'),
        '#name'                    => 'remove_' . $i,
        '#submit'                  => ['::removeDiv'],
        '#ajax'                    => [
          'callback' => '::ajaxUpdateDivsWrapper',
          'wrapper'  => 'divjump-divs-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#attributes'              => ['class' => ['button--danger']],
      ];
    }

    $form['divs_wrapper']['add_div'] = [
      '#type'                    => 'submit',
      '#value'                   => $this->t('+ เพิ่ม Div'),
      '#submit'                  => ['::addDiv'],
      '#ajax'                    => [
        'callback' => '::ajaxUpdateDivsWrapper',
        'wrapper'  => 'divjump-divs-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    // ─── Page Visibility ────────────────────────────────────────────────────
    $form['visibility'] = [
      '#type'  => 'details',
      '#title' => $this->t('การแสดงตามหน้า (Page Visibility)'),
      '#open'  => TRUE,
    ];

    $form['visibility']['visibility_type'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('แสดง DIVjump บนหน้าเพจ'),
      '#options'       => [
        0 => $this->t('ทุกหน้า <em>ยกเว้น</em>หน้าที่ระบุด้านล่าง'),
        1 => $this->t('<em>เฉพาะ</em>หน้าที่ระบุด้านล่าง'),
      ],
      '#default_value' => (int) ($config->get('page_visibility.type') ?? 0),
    ];

    $form['visibility']['pages'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('รายการหน้า'),
      '#description'   => $this->t(
        'กรอก path ทีละบรรทัด ใช้ <code>&lt;front&gt;</code> สำหรับหน้าแรก '
        . 'และ <code>*</code> เป็น wildcard<br>'
        . 'ตัวอย่าง:<br>'
        . '<code>/node/*</code> — ทุกหน้า node<br>'
        . '<code>/blog</code> — หน้า blog เท่านั้น<br>'
        . '<code>&lt;front&gt;</code> — หน้าแรก'
      ),
      '#default_value' => $config->get('page_visibility.pages') ?? '',
      '#rows'          => 6,
    ];

    return parent::buildForm($form, $form_state);
  }

  // ─── AJAX Callbacks ──────────────────────────────────────────────────────

  /**
   * AJAX callback: คืน divs_wrapper ที่อัปเดตแล้ว
   */
  public function ajaxUpdateDivsWrapper(array &$form, FormStateInterface $form_state) {
    return $form['divs_wrapper'];
  }

  /**
   * Submit handler: เพิ่มแถวใหม่
   */
  public function addDiv(array &$form, FormStateInterface $form_state) {
    $divs = $this->extractDivsFromInput($form_state);
    $divs[] = ['div_id' => '', 'row_position' => 3];
    $form_state->set('divs', $divs);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler: ลบแถว
   */
  public function removeDiv(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $index   = (int) str_replace('remove_', '', $trigger['#name']);

    $divs = $this->extractDivsFromInput($form_state);
    unset($divs[$index]);
    $divs = array_values($divs);

    // ถ้าลบหมดแล้ว ให้มีแถวว่าง 1 แถวเสมอ
    if (empty($divs)) {
      $divs = [['div_id' => '', 'row_position' => 3]];
    }

    $form_state->set('divs', $divs);
    $form_state->setRebuild(TRUE);
  }

  /**
   * ดึงค่า divs จาก user input ปัจจุบัน (ระหว่าง AJAX rebuild)
   */
  protected function extractDivsFromInput(FormStateInterface $form_state): array {
    $raw = $form_state->getValue('divs') ?? [];
    $divs = [];
    foreach ($raw as $row) {
      $divs[] = [
        'div_id'       => trim($row['div_id'] ?? ''),
        'row_position' => max(1, (int) ($row['row_position'] ?? 3)),
      ];
    }
    return $divs ?: $form_state->get('divs') ?? [];
  }

  // ─── Validate & Submit ───────────────────────────────────────────────────

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $rows = $form_state->getValue('divs') ?? [];
    foreach ($rows as $i => $row) {
      $div_id = trim($row['div_id'] ?? '');
      if (empty($div_id)) {
        continue; // แถวว่าง — จะถูก filter ออกตอน save
      }
      if (str_starts_with($div_id, '#')) {
        $form_state->setErrorByName(
          "divs][$i][div_id",
          $this->t('แถวที่ @n: ไม่ต้องใส่เครื่องหมาย # นำหน้า', ['@n' => $i + 1])
        );
      }
      if (str_contains($div_id, ' ')) {
        $form_state->setErrorByName(
          "divs][$i][div_id",
          $this->t('แถวที่ @n: Div ID ต้องไม่มีช่องว่าง', ['@n' => $i + 1])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // กรอง divs ที่มี div_id จริงๆ
    $divs = [];
    foreach ($form_state->getValue('divs') ?? [] as $row) {
      $div_id = trim($row['div_id'] ?? '');
      if (!empty($div_id)) {
        $divs[] = [
          'div_id'       => $div_id,
          'row_position' => max(1, (int) ($row['row_position'] ?? 3)),
        ];
      }
    }

    $this->config('divjump.settings')
      ->set('divs', $divs)
      ->set('page_visibility.type', (int) $form_state->getValue('visibility_type'))
      ->set('page_visibility.pages', trim($form_state->getValue('pages') ?? ''))
      ->save();

    parent::submitForm($form, $form_state);

    $this->messenger()->addMessage(
      $this->t('บันทึกการตั้งค่า DivJump เรียบร้อยแล้ว กรุณา <a href="/admin/config/development/performance">clear cache</a> เพื่อให้การเปลี่ยนแปลงมีผล')
    );
  }

}
