<?php

namespace Drupal\divjump\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller สำหรับหน้า Help ของ DivJump.
 */
class DivJumpHelpController extends ControllerBase {

  /**
   * แสดงหน้า help.
   */
  public function help() {
    return [
      '#type' => 'markup',
      '#markup' => divjump_help('help.page.divjump', \Drupal::routeMatch()),
    ];
  }

}
