<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\group\Entity\GroupContentInterface;

/**
 * Provides a common interface for group relation UI text providers.
 */
interface UiTextProviderInterface extends RelationHandlerInterface {

  /**
   * Retrieves the label for the relation.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The relation to retrieve the label for. WARNING: Do not call
   *   $group_content->label() because that method actually points here.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The relation label.
   */
  public function getRelationLabel(GroupContentInterface $group_content);

  /**
   * Retrieves the label for the add page.
   *
   * This is the page where all of the bundles are listed for either adding
   * existing entities to the group or creating new ones inside of it.
   *
   * @param bool $create_mode
   *   Set to FALSE for the add page or TRUE for the create page.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The add page label.
   */
  public function getAddPageLabel($create_mode);

  /**
   * Retrieves the description for the add page.
   *
   * @param bool $create_mode
   *   Set to FALSE for the add page or TRUE for the create page.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The add page description.
   */
  public function getAddPageDescription($create_mode);

  /**
   * Retrieves the title for the add form.
   *
   * @param bool $create_mode
   *   Set to FALSE for the add page or TRUE for the create form.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The add form title.
   */
  public function getAddFormTitle($create_mode);

}
