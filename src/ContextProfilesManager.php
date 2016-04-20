<?php

namespace Drupal\context_profiles;

use Drupal\context\ContextReactionManager;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\context\ContextManager;

/**
 * Defines ContextProfilesManager Class.
 */
class ContextProfilesManager extends PluginBase {

  /**
   * @var array
   */
  private $providerConfig;

  /**
   * @var array
   */
  private $regionConfig;

  /**
   * @var \Drupal\context\ContextReactionManager
   */
  private $contextReactionManager;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  private $blockManager;

  /**
   * ContextProfilesManager constructor.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   * @param \Drupal\context\ContextManager $contextManager
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   */
  function __construct(
    ThemeHandlerInterface $theme_handler,
    ContextManager $context_manager,
    BlockManagerInterface $block_manager,
    ContextReactionManager $context_reaction_manager
  ) {
    $this->themeHandler = $theme_handler;
    $this->contextManager = $context_manager;
    $this->blockManager = $block_manager;
    $this->contextReactionManager = $context_reaction_manager;
  }

  /**
   * Returns the current Theme.
   *
   * @return Theme
   *   Current Theme.
   */
  private function getTheme() {
    return $this->themeHandler->getTheme($this->themeHandler->getDefault());
  }

  /**
   * Create new instance of Reaction.
   *
   * @param string $type
   *
   * @return ContextReactionInterface
   *   New instance.
   */
  public function createReactionInstance($type) {
    return $this->contextReactionManager->createInstance($type);
  }

  /**
   * Get Roles for the current user.
   *
   * @return array
   *   Roles for current user.
   */
  private function getUserRoles() {
    $account = \Drupal::currentUser();

    return $account->getRoles();
  }

  /**
   * Return config options for all assigned roles.
   *
   * @param array $config
   *
   * @return array
   *   Merged roles.
   */
  private function mergeConfigRoles($config) {
    $roles = $this->getUserRoles();
    $allowed_values = array();
    foreach ((array) $roles as $role) {
      if (isset($config[$role]) && !empty($config[$role])) {
        $allowed_values += array_merge($allowed_values, array_keys($config[$role]));
      }
    }

    return array_combine($allowed_values, $allowed_values);
  }

  /**
   * @return ProviderConfig
   *   Provider Configuration.
   */
  public function getProviderConfig() {
    if (!isset($this->providerConfig)) {
      $config = \Drupal::config('context_profiles.settings')
        ->get('roles_providers');
      $this->providerConfig = $this->mergeConfigRoles($config);
    }

    return $this->providerConfig;
  }

  /**
   * Returns the region configuration.
   *
   * @return array
   */
  private function getRegionConfig() {
    if (!isset($this->regionConfig)) {
      $config = \Drupal::config('context_profiles.settings')
        ->get('roles_regions');
      $this->regionConfig = $this->mergeConfigRoles($config);
    }

    return $this->regionConfig;
  }

  /**
   * Return all regions, based on config.
   *
   * @return array
   */
  public function getRegions() {
    $regions = array();
    $info = $this->getTheme()->info;

    $config = $this->getRegionConfig();

    foreach ((array) $info['regions'] as $id => $label) {
      $regions[$id]['label'] = $label;
      $regions[$id]['classes'][] = isset($config[$id]) ? 'region-droppable' : 'region-disabled';
      $regions[$id]['classes'][] = $id;
    }

    // TODO : Add an alter for other modules.
    return $regions;
  }

  /**
   * Get all active contexts.
   *
   * @return array
   *  Active contexts.
   */
  public function getActiveContexts() {
    $contexts = array();
    $unkeyed_contexts = $this->contextManager->getActiveContexts();
    foreach ($unkeyed_contexts as $context) {
      $contexts[$context->id()] = $context;
    }

    return $contexts;
  }

  /**
   * Get all block definitions.
   *
   * @return array
   *  Available blocks.
   */
  public function getAvailableBlockDefinitions() {
    // Only add blocks which work without any available context.
    $blocks = $this->blockManager->getDefinitionsForContexts();

    // Order by category, and then by admin label.
    $blocks = $this->blockManager->getSortedDefinitions($blocks);

    // TODO : Add an alter for other modules.
    return $blocks;
  }

  /**
   * Check if block is draggable and return array of classes.
   *
   * @param $entity
   *
   * @return array $classes
   *   Classes for this block.
   */
  public function addBehaviorClass($entity) {
    $classes = array('block-form');
    $draggable = TRUE;

    $regions = $this->getRegionConfig();
    if (isset($entity['config'])) {
      $classes[] = $entity['context-class'];
      if (!isset($regions[$entity['config']['region']]) || !$entity['active']) {
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

}
