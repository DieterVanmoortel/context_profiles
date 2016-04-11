<?php

/**
 * @file
 * Contains \Drupal\content_callback_examples\Plugin\ContentCallback\Basic.
 */

namespace Drupal\context_profiles\Plugin\MetroDash;

use Drupal\Core\Annotation\Translation;
use Drupal\content_callback\Plugin\PluginBase;
use Drupal\metrodash\Plugin\MetrodashInterface;
use Drupal\Core\Url;

/**
 * Default Dashboard plugin.
 *
 * @Metrodash(
 *   id = "content",
 *   label = @Translation("Content")
 * )
 */
class Content extends PluginBase implements MetrodashInterface {


  public function getLabel() {
    return t('Content');
  }

  public function getid() {
    return 'content';
  }

  public function getFaIcon() {
    return 'copy';
  }

  public function getLinkUrl() {
    return Url::fromRoute('entity.user.collection');
  }
  public function show() {
    return TRUE;
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build[] = array(
      '#type' => 'link',
      '#url' => Url::fromRoute('node.add_page'),
      '#title' => t('Add Content'),
    );

    return $build;
  }

}
