<?php

namespace Drupal\blog\Plugin\BlogHero\Entity;

use Drupal\blog_hero\Plugin\BlogHero\Entity\BlogHeroEntityPluginBase;

/**
 * Hero block for blog_article node type.
 *
 * @BlogHeroEntity(
 *   id = "blog_node_blog_article",
 *   entity_type = "node",
 *   entity_bundle = {"blog_article"}
 * )
 */
class NodeBlogArticle extends BlogHeroEntityPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeroSubtitle() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getEntity();
    return $node->get('body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeroImage() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getEntity();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $node->get('field_pro')->entity;

    return $media->get('field_media_image')->entity->getFileUri();
  }

}
