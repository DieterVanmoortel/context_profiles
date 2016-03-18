<?php
/**
 * @file
 * Contains \Drupal\context_profiles\Form\RegionConfigForm
 */

namespace Drupal\context_profiles\Form;

use Drupal\block\Entity\Block;
use Drupal\context\Reaction\Blocks\Form\BlockFormBase;
use Drupal\context_profiles\ContextProfiles;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\context\Entity\Context;


class BlockLayoutForm extends FormBase {

  private $contextProfile;

  protected $context;

  protected $reaction;

  private function getContextProfile() {
    return $this->contextProfile;
  }

  protected function getSubmitValue() {
    // TODO: Implement getSubmitValue() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_profiles.blocks';
  }

  public function __construct() {
    $this->contextProfile = new ContextProfiles();
  }

  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {

    $this->context = $this->initializeContext($node);

    if ($this->context->hasReaction('blocks')) {
      $this->reaction = $this->context->getReaction('blocks');
    }

    $form['active_contexts'] = array(
      '#type' => 'fieldset',
      '#title' => t('Active contexts'),
    );

//    $form['active_contexts'] = array(
//      '#type' => 'fieldset',
//      '#title' => t('Active contexts'),
//    );

    $form['regions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Regions'),
    );

    $form['disabled'] = array(
      '#type' => 'fieldset',
      '#title' => t('Available Blocks'),
    );

    $region_list = $this->getContextProfile()->getRegions();
    foreach ($region_list as $region_id => $region_name) {
      $form['regions'][$region_id] = array(
        '#type' => 'fieldset',
        '#title' => $region_name,
        '#attributes' => array(
          'class' => array('region-droppable'),
          'id' => $region_id,
        )
      );
    }

    $entities = $this->getContextProfile()->getAvailableBlocks();

    $reactions = $this->context->get('reactions');
    $blocks = isset($reactions['blocks']['blocks']) ? $reactions['blocks']['blocks'] : array();

    $placed_blocks = array();
    foreach ((array)$blocks as $uuid => $config_block) {
      $placed_blocks[$config_block['id']] = $config_block['region'];
      if (isset($entities[$config_block['id']])){
        $entities[$config_block['id']]['uuid'] = $uuid;
      }
    }

    $form['blocks'] = array(
      '#type' => 'value',
      '#value' => $entities,
    );

    foreach ($entities as $id => $entity) {
      $block_field = array(
        '#type' => 'textfield',
        '#title' => $entity['admin_label'],
        '#attributes' => array(
          'class' => array('draggable-block'),
        ),
      );

      // Add placeholder in disabled region
      $form['disabled']['wrap-' . $id] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('form-wrapper'),
          'id' => 'wrap-' . $id
        )
      );

      if (isset($placed_blocks[$id])) {
        $region = $placed_blocks[$id];
        $block_field['#default_value'] = $region;
        $form['regions'][$region][$id] = $block_field;
      }
      else {
        $form['disabled']['wrap-' . $id][$id] = $block_field;
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

    $blocks = $form_state->getValue('blocks');

    foreach($blocks as $block) {

      $plugin = $block['id'];
      $region = $form_state->getValue($plugin);
      if ($region) {
        $configuration = array(
          'id' => 'profile_' . $plugin,
          'status' => TRUE,
          'plugin' => $plugin,
          'visibility' => array(),
          'theme' => 'bartik',
          'region' => $region,
        );
      }
      elseif(isset($block['uuid'])) {
        $this->reaction->removeBlock($plugin);
      }
      if (isset($block['uuid'])) {
        $configuration['uuid'] = $uuid;
      }

      // Add/Update the block.
      if (!isset($configuration['uuid'])) {
        $this->reaction->addBlock($configuration);
      } else {
        $this->reaction->updateBlock($configuration['uuid'], $configuration);
      }

    }

    $this->context->save();
  }


  /**
   * Get or set node specific context.
   *
   * @param $node
   *
   * @return \Drupal\Core\Entity\EntityInterface|null|static
   */
  private function initializeContext($node) {
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

    return $context;
  }

}

