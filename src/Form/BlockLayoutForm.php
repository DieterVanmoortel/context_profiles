<?php

namespace Drupal\context_profiles\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\context\Reaction\Blocks\Form\BlockFormBase;
use Drupal\context\Entity\Context;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines BlockLayoutForm Class.
 */
class BlockLayoutForm extends BlockFormBase {

  /**
   * ContextProfilesManager service.
   *
   * @var ContextProfilesManager
   */
  private $contextProfilesManager;

  /**
   * Stores the context currently being editted.
   *
   * @var Context
   */
  private $current;

  /**
   * List of available contexts.
   *
   * @var array
   */
  private $activeContexts;

  /**
   * Submit button value.
   */
  protected function getSubmitValue() {
    return $this->t('Save Region Configuration');
  }

  /**
   * Returns ContextProfilesManager Service.
   *
   * @return ContextProfilesManager
   */
  private function getContextProfilesManager() {
    if (!isset($this->contextProfilesManager)) {
      $this->contextProfilesManager = \Drupal::service('context_profiles.manager');
    }
    return $this->contextProfilesManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_profiles.blocks';
  }

  /**
   * Active Context Switcher Form.
   *
   * @return array
   */
  private function createActiveContextsForm() {
    $form = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Active contexts'),
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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL) {

    $entity_type = $route_match->getRouteObject()
      ->getOption('_context_profiles_entity_type_id');
    $entity = $route_match->getParameter($entity_type);

    $this->initializeContexts($entity, $entity_type);

    if ($triggering_element = $form_state->getTriggeringElement()) {
      $this->current = $this->activeContexts[$triggering_element['#context']];
    }
    // Reset reaction if needed.
    $this->reaction = $this->current->getReaction('blocks');

    // Store current context as value.
    $form['current_context'] = array(
      '#type' => 'value',
      '#value' => $this->current->id(),
    );

    // Start building the form.
    $form['active_contexts'] = $this->createActiveContextsForm();

    $form['regions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Regions'),
    );
    $form['disabled'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Available Blocks'),
    );
    $form['disabled']['block-lookup'] = array(
      '#type' => 'textfield',
      '#placeholder' => $this->t('Filter by block name'),
    );

    $region_list = $this->getContextProfilesManager()->getRegions();
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

    $available_blocks = $this->getContextProfilesManager()
      ->getAvailableBlockDefinitions();

    $index = 0;
    foreach ($this->activeContexts as $context_id => $context) {
      $reactions = $context->get('reactions');
      $blocks = isset($reactions['blocks']['blocks']) ? $reactions['blocks']['blocks'] : array();

      foreach ((array) $blocks as $config_block) {
        $available_blocks[$config_block['id']] += array(
          'config' => $config_block,
          'context' => $context_id,
          'active' => ($context_id == $this->current->id()),
          'context-class' => 'context-' . $index,
        );
      }
      $index++;
    }

    // Store blocks as value.
    $form['blocks'] = array(
      '#type' => 'value',
      '#value' => $available_blocks,
    );

    $provider_config = $this->getContextProfilesManager()->getProviderConfig();

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
        $block_form['region']['#default_value'] = $region;
        $block_form['label_display']['#default_value'] = $entity['config']['label_display'];

        if (isset($entity['config']['weight'])) {
          $block_form['weight']['#default_value'] = $entity['config']['weight'];
          $form['regions'][$region][$index]['#weight'] = $entity['config']['weight'];
        }
        $form['regions'][$region][$index][$id] = $block_form;
      }
      else {
        $form['disabled'][$entity['provider']]['wrap-' . $index][$id] = $block_form;
      }
      $index++;
    }

    // Form actions traditionally at the bottom.
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
   *   Entity being handled.
   * @param int $index
   *   Delta of current block.
   *
   * @return array
   */
  protected function prepareBlock($entity, $index = 0) {

    // Create new block sub-form.
    $block_form = array(
      '#tree' => TRUE,
      '#type' => 'container',
      '#attributes' => array(
        'class' => $this->getContextProfilesManager()
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

    return $block_form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current = $form_state->getValue('current_context');
    $active_contexts = $form_state->getValue('active_contexts');
    $blocks = $form_state->getValue('blocks');

    // Loop over active contexts, update and save all.
    foreach ($active_contexts as $context_id) {
      $context = Context::load($context_id);
      if ($context) {
        // Existing contexts.
        $reaction = $context->getReaction('blocks');
      }
      elseif ($context_id == $current) {
        // Creating a new context, as it is current.
        $context = $this->current;
        $reaction = $this->reaction;
      }
      else {
        // Not creating a new context, as it is not current.
        continue;
      }

      // Loop all plugins and update, add or remove from context.
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
      // Save every context.
      $context->save();
    }
  }

  /**
   * Get or set entity specific context.
   *
   * @param object $entity
   *   Entity to attach context to.
   * @param string $type
   *   Entity type.
   *
   * @return /stdClass $context
   *   Context
   */
  private function initializeContexts($entity, $type) {
    // Create a unique ID.
    $id = $type . '_profile_' . $entity->id();

    // Try to load existing context, create a new if loading fails.
    $context = Context::load($id);
    if (!$context) {
      $context = Context::create(array(
        'id' => $id,
        'name' => $id,
        'group' => ucfirst($type) . ' profiles',
        'label' => 'Profile : ' . $entity->label(),
      ));

      $conditions['request_path'] = array(
        'id' => 'request_path',
        'pages' => '/' . str_replace('_', '/', $type) . '/' . $entity->id(),
      );

      $context->set('conditions', $conditions);
      // Save only when form is submitted.
    }

    // Create array with active contexts.
    $this->activeContexts[$context->id()] = $context;
    $this->activeContexts += $this->getContextProfilesManager()
      ->getActiveContexts();

    // Set for future reference.
    $this->current = $context;

    if ($context->hasReaction('blocks')) {
      $this->reaction = $this->current->getReaction('blocks');
    }
    else {
      $this->reaction = $this->getContextProfilesManager()
        ->createReactionInstance('blocks');
      $this->current->addReaction($this->reaction->getConfiguration());
    }

    return $this->current;
  }

}
