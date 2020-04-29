<?php

namespace Drupal\group\Plugin;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an interface for group content handlers.
 *
 * This interface can be implemented by group content handlers that require
 * dependency injection.
 *
 * @ingroup group
 */
interface GroupContentHandlerInterface {

  /**
   * Instantiates a new instance of this group content handler.
   *
   * This is a factory method that returns a new instance of this object. The
   * factory should pass any needed dependencies into the constructor of this
   * object, but not the container itself. Every call to this method must return
   * a new instance of this object; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   * @param array $definition
   *   The group content enabler definition.
   *
   * @return static
   *   A new instance of the group content handler.
   *
   * @todo Replace the definition array with a class-based approach like the one
   * entity types use.
   *
   * @internal
   *   Marked as internal because the plugin definitions will become classes in
   *   a future release to further mimic the entity type system. Try to extend
   *   the base handlers shipped with this module. If not, you'll need to update
   *   your implementations when 2.0 lands.
   */
  public static function createInstance(ContainerInterface $container, array $definition);

}
