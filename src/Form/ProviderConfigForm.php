<?php
/**
 * @file
 * Contains \Drupal\context_profiles\Form\ProviderConfigForm
 */

namespace Drupal\context_profiles\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\context_profiles\Form\BaseConfigForm;
use Drupal\context_profiles\ContextProfile;

class ProviderConfigForm extends BaseConfigForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_profiles.config.regions';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $this->buildRolesHeaderForm($form);

    $user_roles = $form['user_roles']['#value'];

    $available_blocks = $this->getContextProfilesManager()
      ->getAvailableBlockDefinitions();

    $providers = array();
    foreach($available_blocks as $block) {
      $provider = $block['provider'];
      if (!isset($providers[$provider])) {
        $providers[$provider] = $provider;
      }
    }

    $default_values = $this->config('context_profiles.settings')
      ->get('roles_providers');

    foreach($providers as $provider) {
      $form['rows'][$provider]['description'] = array(
        '#markup' => $provider,
      );
      foreach ($user_roles as $rid => $role_name) {
        $default = isset($default_values[$rid]) ? $default_values[$rid] : array();
        $form['rows'][$provider][$rid] = array(
          '#title' => $role_name . ': ' . $provider,
          '#title_display' => 'invisible',
          '#wrapper_attributes' => array(
            'class' => array('checkbox'),
          ),
          '#type' => 'checkbox',
          '#default_value' => isset($default[$provider]),
          '#attributes' => array('class' => array('rid-' . $rid, 'js-rid-' . $rid)),
          '#parents' => array($rid, $provider),
        );
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save Block Provider Configuration'),
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
      $provider_settings[$rid] = array_filter($form_state->getValue($rid));
    }

    \Drupal::configFactory()->getEditable('context_profiles.settings')
      ->set('roles_providers', $provider_settings)
      ->save();

    drupal_set_message($this->t('The changes have been saved.'));
    $form_state->setRedirect('context_profiles.settings');
  }

}
