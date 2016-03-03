<?php
/**
 * @file
 * Contains \Drupal\context_profiles\Form\RegionConfigForm
 */

namespace Drupal\context_profiles\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\context_profiles\Form\BaseConfigForm;
use Drupal\context_profiles\ContextProfile;

class RegionConfigForm extends BaseConfigForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_profiles.config.regions';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $this->buildRolesHeaderForm($form);

    $user_roles = $form['user_roles']['#value'];

    $theme_handler = \Drupal::service('theme_handler');
    $theme_name = $theme_handler->getDefault();
    $theme = $theme_handler->getTheme($theme_name);
    $info = $theme->info;
    $regions = $info['regions'];

    $default_values = \Drupal::config('context_profiles.settings')
      ->get('roles_regions');

    foreach($regions as $region => $region_name) {
      $form['regions'][$region]['description'] = array(
        '#markup' => $region_name,
      );
      foreach ($user_roles as $rid => $role_name) {
        $default = $default_values[$rid];
        $form['regions'][$region][$rid] = array(
          '#title' => $role_name . ': ' . $region_name,
          '#title_display' => 'invisible',
          '#wrapper_attributes' => array(
            'class' => array('checkbox'),
          ),
          '#type' => 'checkbox',
          '#default_value' => isset($default[$region]),
          '#attributes' => array('class' => array('rid-' . $rid, 'js-rid-' . $rid)),
          '#parents' => array($rid, $region),
        );
      }

    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save Region Configuration'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('user_roles') as $rid => $name) {
      $region_settings[$rid] = array_filter($form_state->getValue($rid));
    }

    \Drupal::configFactory()->getEditable('context_profiles.settings')
      ->set('roles_regions', $region_settings)
      ->save();

    drupal_set_message($this->t('The changes have been saved.'));
    $form_state->setRedirect('context_profiles.settings');
  }

}