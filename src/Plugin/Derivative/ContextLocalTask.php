<?php

/**
 * @file
 * Contains \Drupal\devel\Plugin\Derivative\DevelLocalTask.
 */

namespace Drupal\context_profiles\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class ContextLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates an DevelLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();

    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {

      $has_edit_path = $entity_type->hasLinkTemplate('block-load');

      if ($has_edit_path) {

        $this->derivatives["$entity_type_id.block_tab"] = array(
          'route_name' => "entity.$entity_type_id." . 'block-load',
          'title' => $this->t('Blocken'),
          'base_route' => "entity.$entity_type_id." .  "edit_form",
          'weight' => 100,
        );


        if ($has_edit_path) {
          $this->derivatives["$entity_type_id.block_tab"] = array(
            'route_name' => "entity.$entity_type_id.block_load",
            'weight' => 100,
            'title' => $this->t('Load'),
            'parent_id' => "devel.entities:$entity_type_id.devel_tab",
          );
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
