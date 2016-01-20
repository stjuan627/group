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
   * Returns the content enabler plugin that handles the group content.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   */
  public function getPlugin();

}
