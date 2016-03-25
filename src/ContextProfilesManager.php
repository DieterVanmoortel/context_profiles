<?php

namespace Drupal\context_profiles;

use Drupal\block\BlockPluginCollection;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\context\ContextManager;


class ContextProfilesManager extends PluginBase {

  protected $theme;

  private $providerConfig;

  private $regionConfig;

  private $blockPluginCollection;

  /**
   * ContextProfilesManager constructor.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   * @param \Drupal\context\ContextManager $contextManager
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   */
  function __construct(
    ThemeHandlerInterface $themeHandler,
    ContextManager $contextManager,
    BlockManagerInterface $blockManager
  ) {
    $this->themeHandler = $themeHandler;
    $this->contextManager = $contextManager;
    $this->blockManager = $blockManager;
  }

  /**
   * @return theme
   */
  private function getTheme() {
    return $this->themeHandler->getTheme($this->themeHandler->getDefault());
  }

  /**
   * @return ProviderConfig
   */
  public function getProviderConfig() {
    if (!isset($this->providerConfig)) {
      // TODO : get user object / permission?
      $this->providerConfig = \Drupal::config('context_profiles.settings')
        ->get('roles_providers')['authenticated']; // DEV !!!
    }

    return $this->providerConfig;
  }

  /**
   * @return \Drupal\block\BlockPluginCollection
   */
  protected function getBlockCollection() {
    if (!$this->blockPluginCollection) {
      $this->blockPluginCollection = new BlockPluginCollection($this->blockManager, array());
    }

    return $this->blockPluginCollection;
  }

  /**
   * @return mixed
   */
  private function getRegionConfig() {
    if (!isset($this->regionConfig)) {
      // TODO : get user object / permission?
      $this->regionConfig = \Drupal::config('context_profiles.settings')
        ->get('roles_regions')['authenticated']; // DEV !!!
    }

    return $this->regionConfig;
  }



  /**
   * @see \Drupal\ctools\Plugin\BlockVariantInterface::getBlock()
   */
  public function getBlock($block_id) {
    return $this->getBlockCollection()->get($block_id);
  }

  /**
   * @return array
   */
  public function getRegions() {
    $regions = array();
    $info = $this->getTheme()->info;

    $config = $this->getRegionConfig();

    foreach ((array) $info['regions'] as $id => $label) {
      $regions[$id]['label'] = $label;
      $regions[$id]['classes'][] = isset($config[$id]) ? 'region-droppable' : 'region-disabled';
    }

    // TODO : Add an alter for other modules


    return $regions;
  }


  public function getActiveContexts() {
    $unkeyed_contexts = $this->contextManager->getActiveContexts();
    foreach($unkeyed_contexts as $context) {
      $contexts[$context->id()] = $context;
    }

    return $contexts;
  }
  /**
   * @return array
   */
  public function getAvailableBlocks() {
    // Only add blocks which work without any available context.
    // TODO : figure out what this does exactly.
    $blocks = $this->blockManager->getDefinitionsForContexts();

    // TODO : Add an alter for other modules


    return $blocks;
  }


  public function createReactionInstance($type) {
    // TODO : FIX THIS !!!!
    $contextReactionManager->createInstance($type);
  }
  /**
   * Check if block is draggable and return array of classes.
   *
   * @param $entity
   *
   * @return array $classes
   */
  public function addBehaviorClassToBlock($entity) {
    $classes = array('block-form');
    $draggable = TRUE;

    $regions = $this->getRegionConfig();
    if (isset($entity['config'])) {
      if (!isset($regions[$entity['config']['region']]) || !isset($entity['active'])) {
        $draggable = FALSE;
      }
    }

    $providers = $this->getProviderConfig();
    if (!isset($providers[$entity['provider']])) {
      $draggable = FALSE;
    }

    if ($draggable) {
      $classes[] = 'draggable-block';
    }
    else {
      $classes[] = 'sortable-block';
    }

    return $classes;
  }

//  /**
//   * Update an existing instance of Block entity.
//   *
//   * @param string $blockId
//   * @param array $configuration
//   *
//   * @return $this
//   */
//  public function updateBlock($blockId, array $configuration) {
//    $existingConfiguration = $this->getBlock($blockId)->getConfiguration();
//
//    $this->getBlocks()
//      ->setInstanceConfiguration($blockId, $configuration + $existingConfiguration);
//
//    return $this; //?
//  }

}
