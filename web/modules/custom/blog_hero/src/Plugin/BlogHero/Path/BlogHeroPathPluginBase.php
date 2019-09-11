<?php

namespace Drupal\blog_hero\Plugin\BlogHero\Path;

use Drupal\blog_hero\Plugin\BlogHero\BlogHeroPluginBase;

/**
 * The base for DloGhero path plugin type.
 */
abstract class BlogHeroPathPluginBase extends BlogHeroPluginBase implements BlogHeroPathPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getMatchPath() {
    return $this->pluginDefinition['match_path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMatchType() {
    return $this->pluginDefinition['match_type'];
  }

}
