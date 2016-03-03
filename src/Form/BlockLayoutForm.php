<?php
/**
 * @file
 * Contains \Drupal\context_profiles\Form\RegionConfigForm
 */

namespace Drupal\context_profiles\Form;

use Drupal\context_profiles\ContextProfiles;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\context\Entity\Context;


class BlockLayoutForm extends BaseConfigForm {

  private $contextProfile;

  private function getContextProfile() {
    return $this->contextProfile;
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

//    $form['context'] = array(
//      '#type' => 'value',
//      '#value' => $this->initializeContext($node),
//    );

    $form['active_contexts'] = array(
      '#type' => 'fieldset',
      '#title' => t('Active contexts'),
    );

    $form['regions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Regions'),
    );

    $region_list = $this->getContextProfile()->getRegions();
    foreach($region_list as $region_id => $region_name) {
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

    $form['disabled'] = array(
      '#type' => 'fieldset',
      '#title' => t('Disabled Blocks'),
    );

    $form['blocks'] = array(
      '#type' => 'value',
      '#value' => $entities,
    );

    foreach ($entities as $id => $entity) {
      $form['disabled'][$id] = array(
        '#type' => 'textfield',
        '#title' => $entity->label(),
        '#attributes' => array(
          'class' => array('draggable-block'),
        ),
      );
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
    $enabled_blocks = array();
    foreach ($form_state->getValue('blocks') as $bid => $name) {
      $block_region = $form_state->getValue($bid);
      if(!empty($block_region)){
        $enabled_blocks['blocks'][$bid] = $block_region;
      }
    }

//
//    $context = Context::create(array(
//      'id' => 'node-1',
//      'name' => 'node-1',
//      'label' => 'Custom context',
//      )
//    );
//    $context->save();


  }

  private function initializeContext($node) {
    dpm($node->getType());
    $id = 'node-' . $node->nid;
    $context = Context::load($id);
    if(!$context) {
      $context = Context::create(array(
        'id' => $id,
        'name' => $id,
        'label' => 'Custom context',
      ));
    }
    dpm($context);
    return $context;
  }

}