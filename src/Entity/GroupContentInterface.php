<?php
/**
 * @file
 * Contains \Drupal\group\Entity\GroupContentInterface.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Group content entity.
 *
 * @ingroup group
 */
interface GroupContentInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Returns the group content type entity the group content uses.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface
   */
  public function getGroupContentType();

  /**
   * Returns the group the group content belongs to.
   *
   * @return \Drupal\group\Entity\GroupInterface
   */
  public function getGroup();

  /**
   * Returns the entity that was added as group content.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity();

  /**
   * Returns the content enabler plugin that handles the group content.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   */
  public function getPlugin();

}
