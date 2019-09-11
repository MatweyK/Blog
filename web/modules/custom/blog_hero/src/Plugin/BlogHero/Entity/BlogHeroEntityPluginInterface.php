<?php

namespace Drupal\blog_hero\Plugin\BlogHero\Entity;

use Drupal\blog_hero\Plugin\BlogHero\BlogHeroPluginInterface;

/**
 * Interface for BlogHero entity plugin type.
 */
interface BlogHeroEntityPluginInterface extends BlogHeroPluginInterface {

  /**
   * Gets entity type id.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityType();

  /**
   * Gets entity bundles.
   *
   * @return array
   *   An array with entity type bundles.
   */
  public function getEntityBundle();

  /**
   * Gets current entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function getEntity();

}
