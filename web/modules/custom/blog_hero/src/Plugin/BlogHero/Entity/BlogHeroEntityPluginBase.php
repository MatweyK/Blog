<?php

namespace Drupal\blog_hero\Plugin\BlogHero\Entity;

use Drupal\blog_hero\Plugin\BlogHero\BlogHeroPluginBase;

/**
 * The base for BlogHero entity plugin type.
 */
abstract class BlogHeroEntityPluginBase extends BlogHeroPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->pluginDefinition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle() {
    return $this->pluginDefinition['entity_bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->configuration['entity'];
  }

}
