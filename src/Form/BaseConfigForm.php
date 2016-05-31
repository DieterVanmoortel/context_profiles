<?php

namespace Drupal\context_profiles\Form;

use Drupal\context_profiles\ContextProfilesManager;
use Drupal\user\Form\UserPermissionsForm;

/**
 * Defines BaseConfigForm Class.
 */
abstract class BaseConfigForm extends UserPermissionsForm {

  /**
   * ContextProfileManager service.
   *
   * @var ContextProfilesManagerManager
   */
  private $contextProfileManager;

  /**
   * Returns the contextProfilesManager object.
   *
   * @return ContextProfilesManager
   *    ContextProfilesManager service.
   */
  protected function getContextProfilesManager() {
    if (!isset($this->contextProfileManager)) {
      $this->contextProfileManager = \Drupal::service('context_profiles.manager');
    }

    return $this->contextProfileManager;
  }

  /**
   * Build the roles header row form.
   *
   * @param array $form
   *   Renderable form array.
   */
  public function buildRolesHeaderForm(&$form) {

    $user_roles = array();
    foreach ($this->getRoles() as $role_name => $role) {
      $user_roles[$role_name] = $role->label();
    }

    // Store $role_names for use when saving the data.
    $form['user_roles'] = array(
      '#type' => 'value',
      '#value' => $user_roles,
    );
    $form['rows'] = array(
      '#type' => 'table',
      '#id' => 'roles',
      '#attributes' => ['class' => ['roles', 'js-roles']],
      '#sticky' => TRUE,
    );
    // Empty cell for aestethic purposes.
    $form['rows']['#header'][] = array(
      'data' => '',
    );
    foreach ($user_roles as $name) {
      $form['rows']['#header'][] = array(
        'data' => $name,
        'class' => array('checkbox'),
      );
    }
  }

}
