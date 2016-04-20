<?php

namespace Drupal\Tests\context_profiles\Unit;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that local task links are available for context profiles.
 *
 * @group context_profiles
 */
class ContextProfilesAdminTest extends WebTestBase {

  /**
   * The bundle being tested.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The content type being tested.
   *
   * @var NodeType
   */
  protected $contentType;

  /**
   * The 'webmaster' user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webmaster;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'tour',
    'block_content',
    'block',
    'context_profiles',
    'node',
  );

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a webmaster user.
    $permissions = array(
      'administer context profiles',
    );
    $this->webmaster = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests that the configuration page is available.
   */
  public function testGeneralConfigurationForm() {
    // Access check for anonymous users.
    $this->drupalLogin();
    $this->drupalGet('/admin/structure/context/profiles');
    $this->assertResponse('403');

    // Check access for webmasters.
    $this->drupalLogin($this->webmaster);
    $this->drupalGet('/admin/structure/context/profiles');
    $this->assertResponse('200');

    // Check existence of links.
    $this->assertRaw('Blocks');

  }

  /**
   * Tests that a contextual link is available for context profiles.
   */
  public function testRegionConfigurationForm() {
    // Check access for webmasters.
    $this->drupalLogin($this->webmaster);
  }

}
