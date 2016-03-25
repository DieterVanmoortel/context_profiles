<?php
/**
 * @file
 * Contains \Drupal\context_profiles\Form\RegionConfigForm
 */

namespace Drupal\context_profiles\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\context\Reaction\Blocks\Form\BlockFormBase;
use Drupal\context\Entity\Context;
use Drupal\context_profiles\ContextProfilesManager;

class BlockLayoutForm extends BlockFormBase {

  private $contextProfileManager;

  protected $context;

  protected $reaction;

  protected $current;

  /**
   * @inheritDoc
   */
  protected function prepareBlock($block_id) {
    // TODO: Implement prepareBlock() method.
  }

  /**
   * @inheritDoc
   */
  protected function getSubmitValue() {
    // TODO: Implement getSubmitValue() method.
  }


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
   * @return array
   */
  private function createActiveContextsForm() {
    $form = array(
      '#type' => 'fieldset',
      '#title' => t('Active contexts'),
    );

    foreach ($this->activeContexts as $context) {
      $form[] = array(
        '#type' => 'button',
        '#value' => $context->label(),
        '#button_type' => 'secondary',
        '#context' => $context->id(),
      );
    }

    return $form;
  }


  /**
   * Build form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\node\NodeInterface|NULL $node
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {

    $this->initializeContexts($node);

    if( $triggering_element = $form_state->getTriggeringElement() ) {
      $this->current = $triggering_element['#context'];
      $this->context = $this->activeContexts[$triggering_element['#context']];
    }
    $this->reaction = $this->context->getReaction('blocks');



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

    // TODO : add link to 'Add a new block' / add/block url
//    $form['more'] = array(
//      '#markup' => 'Add another block',
//    );


    $region_list = $this->getContextProfileManager()->getRegions();
    foreach ($region_list as $region_id => $region) {
      $form['regions'][$region_id] = array(
        '#type' => 'fieldset',
        '#title' => $region['label'],
        '#attributes' => array(
          'class' => $region['classes'],
          'id' => $region_id,
        )
      );
    }

    $available_blocks = $this->getContextProfileManager()->getAvailableBlocks();

    foreach ($this->activeContexts as $context_id => $context) {
      $reactions = $context->get('reactions');
      $blocks = isset($reactions['blocks']['blocks']) ? $reactions['blocks']['blocks'] : array();

      foreach ((array) $blocks as $uuid => $config_block) {
        $available_blocks[$config_block['id']]['config'] = $config_block;
        if($context_id == $this->current) {
          $available_blocks[$config_block['id']]['active'] = TRUE;
        }
      }
    }

    $form['blocks'] = array(
      '#type' => 'value',
      '#value' => $available_blocks,
    );

    $provider_config = $this->getContextProfileManager()->getProviderConfig();

    $index = 0;
    foreach ($available_blocks as $id => $entity) {

      // Create new field.
      $block_field = $this->createDraggableBlockForm($entity, $index);

      if (!isset($form['disabled'][$entity['provider']])) {
        $class = isset($provider_config[$entity['provider']]) ? 'form-provider' : 'disabled-provider';
        // Add provider to disabled region
        $form['disabled'][$entity['provider']] = array(
          '#type' => 'fieldset',
          '#title' => $entity['provider'],
          '#attributes' => array(
            'class' => array($class),
          ),
        );
      }

      // Add placeholder in disabled region
      $form['disabled'][$entity['provider']]['wrap-' . $index] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('block-placeholder'),
          'id' => 'wrap-' . $index
        )
      );

      if (isset($entity['config'])) {

        $region = $entity['config']['region'];
        // TODO : create loop

        $block_field['region']['#default_value'] = $region;
        $block_field['weight']['#default_value'] = $entity['config']['weight'];
        $block_field['label_display']['#default_value'] = $entity['config']['label_display'];
        $form['regions'][$region][$index][$id] = $block_field;
      }
      else {
        $form['disabled'][$entity['provider']]['wrap-' . $index][$id] = $block_field;
      }

      $index++;
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
   * Create a draggable block subform.
   *
   * @param $entity
   * @param $index
   *
   * @return array
   */
  private function createDraggableBlockForm($entity, $index) {

    // Create new field.
    $block_field = array(
      '#tree' => TRUE,
      '#type' => 'container',
      '#attributes' => array(
        'class' => $this->getContextProfileManager()->addBehaviorClassToBlock($entity),
        'plugin' => $index,
      ),
    );
    $block_field['admin-label'] = array(
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $entity['admin_label'],
    );
    $block_field['region'] = array(
      '#type' => 'textfield',
      '#attributes' => array(
        'class' => array('block-region'),
      ),
    );
    $block_field['weight'] = array(
      '#type' => 'weight',
      '#attributes' => array(
        'class' => array('block-weight'),
      ),
    );
    $block_field['label_display'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Block title'),
      '#return_value' => 'visible',
      '#attributes' => array(
        'class' => array('block-title'),
      ),
    );
    $block_field['reset-block'] = array(
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => 'X',
      '#attributes' => array(
        'class' => array('reset-block'),
      ),
    );

    return $block_field;
  }


  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $blocks = $form_state->getValue('blocks');
    // Loop all plugins and add or remove from context.
    foreach ($blocks as $id => $block) {
      $plugin = $block['id'];
      $submitted_values = $form_state->getValue($id);
      if (!empty($submitted_values['region'])) {
        $configuration = $submitted_values;

        // Add/Update the block.
        if (!isset($block['config'])) {
//          $block = $this->prepareBlock($plugin);
          dpm('Disabled: adding ' . $plugin);

        }
        else {
          dpm('updating block');
          $this->reaction->updateBlock($block['config']['uuid'], $configuration);
        }
      }
      elseif (isset($block['config'])) {
        $this->reaction->removeBlock($block['config']['uuid']);
      }

    }
    // Only save if we have blocks.
    if ($this->context->hasReaction('blocks')) {
//      dpm('not saving context');
      //$this->context->save();
    }
  }


  /**
   * Get or set node specific context.
   *
   * @param $node
   *
   * @return \Drupal\Core\Entity\EntityInterface|null|static
   */
  private function initializeContexts($node) {
    $this->activeContexts = $this->getContextProfileManager()->getActiveContexts();

    $id = 'node_profile_' . $node->id();
    $context = Context::load($id);
    if (!$context) {
      $context = Context::create(array(
        'id' => $id,
        'name' => $id,
        'group' => 'Node profiles',
        'label' => 'Profile : ' . $node->getTitle(),
      ));

      $conditions['request_path'] = array(
        'id' => 'request_path',
        'pages' => '/node/' . $node->id(),
      );
      $context->set('conditions', $conditions);

      // Save only when form is submitted.
    }
    $this->context = $context;
    $this->current = $context->id();
    $this->activeContexts[$context->id()] = $context;

    if ($context->hasReaction('blocks')) {
      $this->reaction = $context->getReaction('blocks');
    }
    else {
      // TODO : FIX THIS !!
      $this->reaction = $this->getContextProfileManager()->createReactionInstance('blocks');
      $context->addReaction($this->reaction->getConfiguration());
    }

    return $context;
  }

}

