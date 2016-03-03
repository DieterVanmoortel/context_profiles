<?php

namespace Drupal\context_profiles;

class ContextProfiles {

  public function getRegions() {
    $theme_handler = \Drupal::service('theme_handler');
    $theme_name = $theme_handler->getDefault();
    $theme = $theme_handler->getTheme($theme_name);
    $info = $theme->info;
    return $info['regions'];
  }

  public function getAvailableBlocks() {
    return entity_load_multiple('block');
  }

}