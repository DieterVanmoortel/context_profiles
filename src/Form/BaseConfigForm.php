<?php
/**
 * @file
 * Contains \Drupal\context_profiles\Form\RegionConfigForm
 */

namespace Drupal\context_profiles\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserPermissionsForm;

abstract class BaseConfigForm extends UserPermissionsForm {

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
    $form['regions'] = array(
      '#type' => 'table',
      //'#header' => array($this->t('Roles')),
      '#id' => 'roles',
      '#attributes' => ['class' => ['roles', 'js-roles']],
      '#sticky' => TRUE,
    );

    foreach ($user_roles as $name) {
      $form['regions']['#header'][] = array(
        'data' => $name,
        'class' => array('checkbox'),
      );
    }

    return $form;
  }

}