<?php

namespace Drupal\context_profiles\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Context Profile routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $add_block_route = $collection->get('block_content.add_page');
    $admin_route = $collection->get('context_profiles.settings');

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route = $this->getContextProfileRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.context_profile", $route);
        // Direct action link to add new content block.
        if ($add_block_route) {
          $collection->add("$entity_type_id.block_add", $add_block_route);
        }
        // Direct action link to the settings page.
        if (isset($admin_route) && \Drupal::currentUser()->hasPermission('administer context profiles')) {
          $collection->add("$entity_type_id.admin", $admin_route);
        }
      }
    }
  }

  /**
   * Gets the Context Profile UI route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getContextProfileRoute(EntityTypeInterface $entity_type) {
    if ($route_load = $entity_type->getLinkTemplate('context-profile')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($route_load);
      $route
        ->addDefaults([
          '_form' => '\Drupal\context_profiles\Form\BlockLayoutForm',
          '_title' => 'Blocks',
        ])
        ->addRequirements([
          '_permission' => 'use context profile ui',
        ])
        ->setOption('_context_profiles_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ])
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', 100);
    return $events;
  }

}
