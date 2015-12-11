<?php

/**
 * @file
 * Contains \Drupal\context_profiles\Controller\AdminController
 */

namespace Drupal\context_profiles\Controller;

use Drupal\Core\Controller\ControllerBase;


class AdminController extends ControllerBase {

  public function settings() {
    return array(
        '#type' => 'markup',
        '#markup' => $this->t('Hello, World!'),
    );
  }

}
