<?php

namespace Drupal\divjump\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Help controller for the DivJump module.
 */
class DivJumpHelpController extends ControllerBase {

  /**
   * Returns the help page content.
   */
  public function help() {
    return [
      '#type'   => 'markup',
      '#markup' => divjump_help('help.page.divjump', \Drupal::routeMatch()),
    ];
  }

}
