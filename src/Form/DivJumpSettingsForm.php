<?php

namespace Drupal\divjump\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings form for the DivJump module.
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

    // Load divs from form state (during AJAX) or from config (first load).
    if ($form_state->get('divs') === NULL) {
      $saved = $config->get('divs') ?? [];
      $form_state->set('divs', !empty($saved) ? $saved : [['div_id' => '', 'row_position' => 3, 'views_selector' => '']]);
    }
    $divs = $form_state->get('divs');

    $form['description'] = [
      '#type'   => 'markup',
      '#markup' => '<div class="messages messages--info">'
        . $this->t('<strong>DivJump</strong>: Moves div elements and inserts them between specified <code>.views-row</code> elements using JavaScript.')
        . '</div>',
    ];

    // ── Div entries table ─────────────────────────────────────────────────────
    $form['divs_wrapper'] = [
      '#type'   => 'fieldset',
      '#title'  => $this->t('Div entries to move'),
      '#prefix' => '<div id="divjump-divs-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['divs_wrapper']['divs'] = [
      '#type'   => 'table',
      '#header' => [
        $this->t('Div ID (without #)'),
        $this->t('Insert after row #'),
        $this->t('Views container selector (optional)'),
        $this->t(''),
      ],
      '#empty'  => $this->t('No entries yet. Click "+ Add Div" to add one.'),
    ];

    foreach ($divs as $i => $div) {
      $form['divs_wrapper']['divs'][$i]['div_id'] = [
        '#type'          => 'textfield',
        '#default_value' => $div['div_id'] ?? '',
        '#placeholder'   => 'block-thex-adsenseadunit-3',
        '#maxlength'     => 255,
        '#attributes'    => ['style' => 'min-width:260px'],
      ];

      $form['divs_wrapper']['divs'][$i]['row_position'] = [
        '#type'          => 'number',
        '#default_value' => $div['row_position'] ?? 3,
        '#min'           => 1,
        '#max'           => 999,
        '#size'          => 5,
        '#description'   => $this->t('1-based'),
      ];

      $form['divs_wrapper']['divs'][$i]['views_selector'] = [
        '#type'          => 'textfield',
        '#default_value' => $div['views_selector'] ?? '',
        '#placeholder'   => '.node-page',
        '#maxlength'     => 255,
        '#attributes'    => ['style' => 'min-width:200px'],
        '#description'   => $this->t('Scope <code>.views-row</code> to this container. Leave empty to search the whole page.'),
      ];

      $form['divs_wrapper']['divs'][$i]['remove'] = [
        '#type'                    => 'submit',
        '#value'                   => $this->t('Remove'),
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
      '#value'                   => $this->t('+ Add Div'),
      '#submit'                  => ['::addDiv'],
      '#ajax'                    => [
        'callback' => '::ajaxUpdateDivsWrapper',
        'wrapper'  => 'divjump-divs-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    // ── Page Visibility ───────────────────────────────────────────────────────
    $form['visibility'] = [
      '#type'  => 'details',
      '#title' => $this->t('Page Visibility'),
      '#open'  => TRUE,
    ];

    $form['visibility']['visibility_type'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Show DivJump on'),
      '#options'       => [
        0 => $this->t('All pages <em>except</em> those listed below'),
        1 => $this->t('<em>Only</em> the pages listed below'),
      ],
      '#default_value' => (int) ($config->get('page_visibility.type') ?? 0),
    ];

    $form['visibility']['pages'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Pages'),
      '#description'   => $this->t(
        'Enter one path per line. Use <code>&lt;front&gt;</code> for the front page '
        . 'and <code>*</code> as a wildcard.<br>'
        . 'Examples:<br>'
        . '<code>/node/*</code> — all node pages<br>'
        . '<code>/blog</code> — the blog page only<br>'
        . '<code>&lt;front&gt;</code> — the front page'
      ),
      '#default_value' => $config->get('page_visibility.pages') ?? '',
      '#rows'          => 6,
    ];

    return parent::buildForm($form, $form_state);
  }

  // ── AJAX Callbacks ────────────────────────────────────────────────────────

  /**
   * AJAX callback: returns the updated divs_wrapper element.
   */
  public function ajaxUpdateDivsWrapper(array &$form, FormStateInterface $form_state) {
    return $form['divs_wrapper'];
  }

  /**
   * Submit handler: add a new empty row.
   */
  public function addDiv(array &$form, FormStateInterface $form_state) {
    $divs   = $this->extractDivsFromInput($form_state);
    $divs[] = ['div_id' => '', 'row_position' => 3, 'views_selector' => ''];
    $form_state->set('divs', $divs);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler: remove a row by index.
   */
  public function removeDiv(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $index   = (int) str_replace('remove_', '', $trigger['#name']);

    $divs = $this->extractDivsFromInput($form_state);
    unset($divs[$index]);
    $divs = array_values($divs);

    if (empty($divs)) {
      $divs = [['div_id' => '', 'row_position' => 3, 'views_selector' => '']];
    }

    $form_state->set('divs', $divs);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Reads current div values from user input during an AJAX rebuild.
   */
  protected function extractDivsFromInput(FormStateInterface $form_state): array {
    $raw  = $form_state->getValue('divs') ?? [];
    $divs = [];
    foreach ($raw as $row) {
      $divs[] = [
        'div_id'         => trim($row['div_id'] ?? ''),
        'row_position'   => max(1, (int) ($row['row_position'] ?? 3)),
        'views_selector' => trim($row['views_selector'] ?? ''),
      ];
    }
    return $divs ?: ($form_state->get('divs') ?? []);
  }

  // ── Validate & Submit ─────────────────────────────────────────────────────

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('divs') ?? [] as $i => $row) {
      $div_id = trim($row['div_id'] ?? '');
      if (empty($div_id)) {
        continue;
      }
      if (str_starts_with($div_id, '#')) {
        $form_state->setErrorByName(
          "divs][$i][div_id",
          $this->t('Row @n: Do not include a leading # character.', ['@n' => $i + 1])
        );
      }
      if (str_contains($div_id, ' ')) {
        $form_state->setErrorByName(
          "divs][$i][div_id",
          $this->t('Row @n: Div ID must not contain spaces.', ['@n' => $i + 1])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $divs = [];
    foreach ($form_state->getValue('divs') ?? [] as $row) {
      $div_id = trim($row['div_id'] ?? '');
      if (!empty($div_id)) {
        $divs[] = [
          'div_id'         => $div_id,
          'row_position'   => max(1, (int) ($row['row_position'] ?? 3)),
          'views_selector' => trim($row['views_selector'] ?? ''),
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
      $this->t('DivJump settings saved. Please <a href="/admin/config/development/performance">clear the cache</a> for changes to take effect.')
    );
  }

}
