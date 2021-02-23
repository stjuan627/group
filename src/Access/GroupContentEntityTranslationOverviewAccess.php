<?php

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for grouped entity translation overview.
 */
class GroupContentEntityTranslationOverviewAccess implements AccessInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a GroupContentTranslationEntityOverviewAccess object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * Checks access to the translation overview for the entity and bundle.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, $entity_type_id) {
    if (!$this->languageManager->isMultilingual()) {
      return AccessResult::neutral();
    }

    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    if (!$entity || !$entity->isTranslatable() || $entity->getUntranslated()->language()->isLocked()) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    // We know the entity is translatable now, so simply call for "translate"
    // access and if it is a grouped entity, Group's access layer will kick in
    // correctly, forbidding access if you do not have the group permission.
    return $entity->access('translate', $account, TRUE);
  }

}
