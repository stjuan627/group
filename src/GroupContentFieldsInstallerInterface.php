<?php

namespace Drupal\group;

use Drupal\group\Plugin\GroupContentEnablerInterface;

/**
 * Defines the Group Content Fields Installer Interface.
 */
interface GroupContentFieldsInstallerInterface {

  /**
   * Checks if the status field should be installed for this plugin.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The group content enabler plugin.
   *
   * @return bool
   *   The answer.
   */
  public function shouldInstallStatus(GroupContentEnablerInterface $plugin);

  /**
   * Installs status field for a group content enabler plugin.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The group content enabler plugin.
   */
  public function installStatusField(GroupContentEnablerInterface $plugin);

}
