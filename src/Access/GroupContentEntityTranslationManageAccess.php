<?php

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for grouped entity translation management.
 */
class GroupContentEntityTranslationManageAccess implements AccessInterface {

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
   * Checks translation access for the entity and operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $source
   *   (optional) For a create operation, the language code of the source.
   * @param string $target
   *   (optional) For a create operation, the language code of the translation.
   * @param string $language
   *   (optional) For an update or delete operation, the language code of the
   *   translation being updated or deleted.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, $source = NULL, $target = NULL, $language = NULL, $entity_type_id = NULL) {
    if (!$this->languageManager->isMultilingual()) {
      return AccessResult::neutral();
    }

    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    if (!$entity || !$entity->isTranslatable() || $entity->getUntranslated()->language()->isLocked()) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $operation = $route->getRequirement('_access_content_translation_manage');
    if (in_array($operation, ['update', 'delete'])) {
      // Translation operations cannot be performed on the default translation.
      if ($language->getId() == $entity->getUntranslated()->language()->getId()) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
    }
    elseif ($operation == 'create') {
      $translations = $entity->getTranslationLanguages();
      $languages = $this->languageManager->getLanguages();
      $source_language = $this->languageManager->getLanguage($source) ?: $entity->language();
      $target_language = $this->languageManager->getLanguage($target) ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);

      $is_new_translation = ($source_language->getId() != $target_language->getId()
        && isset($languages[$source_language->getId()])
        && isset($languages[$target_language->getId()])
        && !isset($translations[$target_language->getId()]));

      if (!$is_new_translation) {
        return AccessResult::neutral()->addCacheableDependency($entity);
      }
    }

    // We know the entity is translatable now, so simply call for "translate"
    // access and if it is a grouped entity, Group's access layer will kick in
    // correctly, forbidding access if you do not have the group permission.
    return $entity->access('translate', $account, TRUE);
  }

}
