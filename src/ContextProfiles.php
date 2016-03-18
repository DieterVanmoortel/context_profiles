<?php

namespace Drupal\context_profiles;

use Drupal\block\Entity\Block;

class ContextProfiles {

  public function getRegions() {
    $theme_handler = \Drupal::service('theme_handler');
    $theme_name = $theme_handler->getDefault();
    $theme = $theme_handler->getTheme($theme_name);
    $info = $theme->info;
    return $info['regions'];
  }

  public function getAvailableBlocks() {
    $block_manager = \Drupal::service('plugin.manager.block');

    // Only add blocks which work without any available context.
    $blocks = $block_manager->getDefinitionsForContexts();
    // Order by category, and then by admin label.
//    $blocks = $block_manager->getSortedDefinitions($blocks);

    return $blocks;
  }

}
