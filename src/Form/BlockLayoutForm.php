<?php

namespace Drupal\context_profiles\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\context\Reaction\Blocks\Form\BlockFormBase;
use Drupal\context\Entity\Context;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines BlockLayoutForm Class.
 */
class BlockLayoutForm extends BlockFormBase {

  /**
   * @var ContextProfilesManager
   */
  private $contextProfileManager;

  /**
   * @var Context
   */
  private $current;

  /**
   * @var array
   *  ActiveContexts.
   */
  private $activeContexts;

  /**
   * @inheritDoc
   */
  protected function getSubmitValue() {
    return $this->t('Save Region Configuration');
  }

  /**
   * @return ContextProfilesManager
   */
  private function getContextProfileManager() {
    if (!isset($this->contextProfileManager)) {
      $this->contextProfileManager = \Drupal::service('context_profiles.manager');
    }
    return $this->contextProfileManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_profiles.blocks';
  }

  /**
   * Create a the Acctive Context Switcher Form.
   *
   * @return array $form
   */
  private function createActiveContextsForm() {
    $form = array(
      '#type' => 'fieldset',
      '#title' => t('Active contexts'),
    );
    $index = 0;
    foreach ($this->activeContexts as $context) {
      $form[] = array(
        '#type' => 'button',
        '#value' => $context->label(),
        '#button_type' => $this->current->id() === $context->id() ? 'primary' : 'secondary',
        '#context' => $context->id(),
        '#attributes' => array(
          'class' => array('context-' . $index),
        ),
      );
      $contexts[] = $context->id();
      $index++;
    }

    $form['active_contexts'] = array(
      '#type' => 'value',
      '#value' => $contexts,
    );

    return $form;
  }


  /**
   * Build form.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param RouteMatchInterface $route_match
   *
   * @return array
   *  returns a renderable form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL) {

    $entity_type = $route_match->getRouteObject()
      ->getOption('_context_profiles_entity_type_id');
    $entity = $route_match->getParameter($entity_type);

    $this->initializeContexts($entity, $entity_type);

    if ($triggering_element = $form_state->getTriggeringElement()) {
      $this->current = $this->activeContexts[$triggering_element['#context']];
    }

    $form['current_context'] = array(
      '#type' => 'value',
      '#value' => $this->current->id(),
    );

    $this->reaction = $this->current->getReaction('blocks');

    // Start building the form.
    $form['active_contexts'] = $this->createActiveContextsForm();

    $form['regions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Regions'),
    );
    $form['disabled'] = array(
      '#type' => 'fieldset',
      '#title' => t('Available Blocks'),
    );

    $form['disabled']['block-lookup'] = array(
      '#type' => 'textfield',
      '#placeholder' => 'Find blocks',
    );

    $region_list = $this->getContextProfileManager()->getRegions();
    foreach ($region_list as $region_id => $region) {
      $form['regions'][$region_id] = array(
        '#type' => 'fieldset',
        '#title' => $region['label'],
        '#attributes' => array(
          'class' => $region['classes'],
          'id' => $region_id,
        ),
      );
    }

    $available_blocks = $this->getContextProfileManager()
      ->getAvailableBlockDefinitions();

    $index = 0;
    foreach ($this->activeContexts as $context_id => $context) {
      $reactions = $context->get('reactions');
      $blocks = isset($reactions['blocks']['blocks']) ? $reactions['blocks']['blocks'] : array();

      foreach ((array) $blocks as $config_block) {
        $available_blocks[$config_block['id']]['config'] = $config_block;
        $available_blocks[$config_block['id']]['context'] = $context_id;
        $available_blocks[$config_block['id']]['active'] = ($context_id == $this->current->id());
        $available_blocks[$config_block['id']]['context-class'] = 'context-' . $index;
      }
      $index++;
    }

    $form['blocks'] = array(
      '#type' => 'value',
      '#value' => $available_blocks,
    );

    $provider_config = $this->getContextProfileManager()->getProviderConfig();

    $index = 0;
    foreach ($available_blocks as $id => $entity) {
      // Create subform.
      $block_form = $this->prepareBlock($entity, $index);

      if (!isset($form['disabled'][$entity['provider']])) {
        $class = isset($provider_config[$entity['provider']]) ? 'form-provider' : 'disabled-provider';
        // Add provider to disabled region.
        $form['disabled'][$entity['provider']] = array(
          '#type' => 'fieldset',
          '#title' => $entity['provider'],
          '#attributes' => array(
            'class' => array($class),
          ),
        );
      }

      // Add placeholder in disabled region.
      $form['disabled'][$entity['provider']]['wrap-' . $index] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('block-placeholder'),
          'id' => 'wrap-' . $index,
        ),
      );

      if (isset($entity['config'])) {
        $region = $entity['config']['region'];
        $form['regions'][$region][$index][$id] = $block_form;
        $form['regions'][$region][$index]['#weight'] = $entity['config']['weight'];
      }
      else {
        $form['disabled'][$entity['provider']]['wrap-' . $index][$id] = $block_form;
      }
      $index++;
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->getSubmitValue(),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * Create a draggable block subform.
   *
   * @param array $entity
   * @param int $index
   *
   * @return array
   */
  protected function prepareBlock($entity, $index = 0) {

    // Create new field.
    $block_form = array(
      '#tree' => TRUE,
      '#type' => 'container',
      '#attributes' => array(
        'class' => $this->getContextProfileManager()
          ->addBehaviorClass($entity),
        'plugin' => $index,
      ),
    );
    $block_form['admin-label'] = array(
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $entity['admin_label'],
    );
    $block_form['region'] = array(
      '#type' => 'textfield',
      '#attributes' => array(
        'class' => array('block-region'),
      ),
    );
    $block_form['weight'] = array(
      '#type' => 'weight',
      '#attributes' => array(
        'class' => array('block-weight'),
      ),
    );
    $block_form['label_display'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Block title'),
      '#return_value' => 'visible',
      '#attributes' => array(
        'class' => array('block-title'),
      ),
    );
    $block_form['reset-block'] = array(
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => 'X',
      '#attributes' => array(
        'class' => array('reset-block'),
      ),
    );

    if (isset($entity['config'])) {
      $block_form['region']['#default_value'] = $entity['config']['region'];
      $block_form['weight']['#default_value'] = $entity['config']['weight'];
      $block_form['label_display']['#default_value'] = $entity['config']['label_display'];
    }

    return $block_form;
  }


  /**
   * Form Submission handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current = $form_state->getValue('current_context');
    $active_contexts = $form_state->getValue('active_contexts');
    $blocks = $form_state->getValue('blocks');

    foreach ($active_contexts as $context_id) {
      if ($context_id == $current) {
        $context = $this->current;
        $reaction = $this->reaction;
      }
      else {
        $context = Context::load($context_id);
        $reaction = $context->getReaction('blocks');
      }

      // Loop all plugins and add or remove from context.
      foreach ($blocks as $id => $block) {
        $new_block = !isset($block['context']) && $current == $context_id;
        $update_existing_block = isset($block['context']) && $block['context'] == $context_id;

        if ($new_block || $update_existing_block) {
          $configuration = $form_state->getValue($id);
          if (!empty($configuration['region'])) {
            if (isset($block['config'])) {
              // Update existing block.
              $reaction->updateBlock($block['config']['uuid'], $configuration);
            }
            else {
              // Add new block.
              $configuration += $block;
              $configuration['id'] = $id;
              $configuration['theme'] = $this->themeHandler->getDefault();
              $reaction->addBlock($configuration);
            }
          }
          elseif (isset($block['config'])) {
            // Remove existing blocks.
            $reaction->removeBlock($block['config']['uuid']);
          }
        }
      }

      $context->save();
    } // end contexts loop
  }

  /**
   * Get or set entity specific context.
   *
   * @param object $entity
   * @param string $type
   *
   * @return Context
   */
  private function initializeContexts($entity, $type) {

    $id = $type . '_profile_' . $entity->id();

    $context = Context::load($id);
    if (!$context) {
      $context = Context::create(array(
        'id' => $id,
        'name' => $id,
        'group' => $type . ' profiles',
        'label' => 'Profile : ' . $entity->label(),
      ));

      $conditions['request_path'] = array(
        'id' => 'request_path',
        'pages' => '/' . str_replace('_', '/', $type) . '/' . $entity->id(),
      );

      $context->set('conditions', $conditions);
      // Save only when form is submitted.
    }

    $this->current = $context;
    $this->activeContexts[$context->id()] = $context;

    $this->activeContexts += $this->getContextProfileManager()
      ->getActiveContexts();

    if ($context->hasReaction('blocks')) {
      $this->reaction = $this->current->getReaction('blocks');
    }
    else {
      $this->reaction = $this->getContextProfileManager()
        ->createReactionInstance('blocks');
      $this->current->addReaction($this->reaction->getConfiguration());
    }

    return $this->current;
  }

}
