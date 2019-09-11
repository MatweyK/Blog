<?php

namespace Drupal\blog_hero\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\blog_hero\Plugin\BlogHeroPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Blog Hero' block.
 *
 * @Block(
 *   id = "blog_hero",
 *   admin_label = @Translation("Blog Hero"),
 *   category = @Translation("Custom")
 * )
 */
class BlogHeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The plugin manager for blog hero entity plugins.
   *
   * @var \Drupal\blog_hero\Plugin\BlogHeroPluginManager
   */
  protected $blogHeroEntityManager;

  /**
   * The plugin manager for blog hero path plugins.
   *
   * @var \Drupal\blog_hero\Plugin\BlogHeroPluginManager
   */
  protected $blogHeroPathManager;

  /**
   * Constructs a new BlogHeroBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\blog_hero\Plugin\BlogHeroPluginManager $blog_hero_entity
   *   The plugin manager for blog hero entity plugins.
   * @param \Drupal\blog_hero\Plugin\BlogHeroPluginManager $blog_hero_path
   *   The plugin manager for blog hero path plugins.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlogHeroPluginManager $blog_hero_entity, BlogHeroPluginManager $blog_hero_path) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->blogHeroEntityManager = $blog_hero_entity;
    $this->blogHeroPathManager = $blog_hero_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.blog_hero.entity'),
      $container->get('plugin.manager.blog_hero.path')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity_plugins = $this->blogHeroEntityManager->getSuitablePlugins();
    $path_plugins = $this->blogHeroPathManager->getSuitablePlugins();
    $plugins = $entity_plugins + $path_plugins;
    uasort($plugins, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    $plugin = end($plugins);

    if ($plugin['plugin_type'] == 'entity') {
      /** @var \Drupal\blog_hero\Plugin\BlogHero\BlogHeroPluginInterface $instance */
      $instance = $this->blogHeroEntityManager->createInstance($plugin['id'], ['entity' => $plugin['entity']]);
    }

    if ($plugin['plugin_type'] == 'path') {
      $instance = $this->blogHeroPathManager->createInstance($plugin['id']);
    }

    $build['content'] = [
      '#theme' => 'blog_hero',
      '#title' => $instance->getHeroTitle(),
      '#subtitle' => $instance->getHeroSubtitle(),
      '#image' => $instance->getHeroImage(),
      '#video' => $instance->getHeroVideo(),
      '#plugin_id' => $instance->getPluginId(),
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [
      'url.path',
    ];
  }

}
