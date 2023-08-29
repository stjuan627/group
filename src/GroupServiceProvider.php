<?php

namespace Drupal\group;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Alters existing services for the Group module.
 */
class GroupServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Ensures you can update to enable VariationCache without the container
    // choking on the variation_cache_factory service no longer being there.
    if (!$container->hasDefinition('variation_cache_factory')) {
      $definition = new Definition('\Drupal\group\VariationCacheFactoryUpdateFix');
      $definition->setPublic(TRUE);
      $container->addDefinitions(['variation_cache_factory' => $definition]);
    }

    // We need to override these access services to be able to explicitly
    // set allowed access for the entity translation routes.
    // Just implementing the access_check tag in the services file won't work.
    // See https://drupal.org/project/drupal/issues/2991698.
    if ($container->hasDefinition('content_translation.overview_access')) {
      $definition = $container->getDefinition('content_translation.overview_access');
      $definition->setClass('\Drupal\group\Access\GroupTranslationOverviewAccessCheck');
    }
    if ($container->hasDefinition('content_translation.manage_access')) {
      $definition = $container->getDefinition('content_translation.manage_access');
      $definition->setClass('\Drupal\group\Access\GroupTranslationManageAccessCheck');
    }
  }

}
