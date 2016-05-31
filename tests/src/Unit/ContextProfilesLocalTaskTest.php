<?php

namespace Drupal\Tests\context_profiles\Unit;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that local task links are available for context profiles.
 *
 * @group context_profiles
 */
class ContextProfilesLocalTaskTest extends WebTestBase {

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
    'context',
    'context_profiles',
    'node',
    'block_content',
    'devel',
  );

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a content type.
    $this->bundle = $this->randomMachineName();
    $this->contentType = $this->drupalCreateContentType(array('type' => $this->bundle));

    // Create a webmaster user.
    $permissions = array(
      'use context profile ui',
      'administer nodes',
      "edit any $this->bundle content",
    );
    $this->webmaster = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests that a contextual link is available for context profiles.
   */
  public function testLocalTasks() {
    $this->drupalLogin($this->webmaster);

    // Create a node.
    $title = $this->randomString();
    $node = $this->drupalCreateNode(array(
      'type' => $this->bundle,
      'title' => $title,
    ));

    // Check that the context profiles link appears on the node page.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse('200');
    $this->assertRaw('Blocks');

    // Check access to UI.
    $this->drupalGet('node/' . $node->id() . '/blocks');
    $this->assertResponse('200');
    $this->assertRaw('Active contexts');

  }

}
