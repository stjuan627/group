<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Base class for group token replacement tests.
 */
abstract class GroupTokenReplaceKernelTestBase extends GroupKernelTestBase {

  /**
   * The interface language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $interfaceLanguage;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * Sets up the test environment.
   *
   * This method is called before each test is run. It initializes the necessary
   * services and properties required for the tests, including language manager
   * and token service.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->interfaceLanguage = \Drupal::languageManager()->getCurrentLanguage();
    $this->tokenService = \Drupal::token();
  }

}
