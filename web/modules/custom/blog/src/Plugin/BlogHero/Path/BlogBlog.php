<?php

namespace Drupal\blog\Plugin\BlogHero\Path;

use Drupal\blog_hero\Plugin\BlogHero\Path\BlogHeroPathPluginBase;
use Drupal\media\MediaInterface;

/**
 * Hero block for path.
 *
 * @BlogHeroPath(
 *   id = "blog_blog",
 *   match_type = "listed",
 *   match_path = {"/blog"}
 * )
 */
class BlogBlog extends BlogHeroPathPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeroSubtitle() {
    return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit,
     sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. 
     Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris 
     nisi ut aliquip ex ea commodo consequat.';
  }

  /**
   * {@inheritdoc}
   */
  public function getHeroImage() {
    /**
     * @var \Drupal\media\MediaStorage $media_storage
     */
    $media_storage = $this->getEntityTypeManager()->getStorage('media');
    $media_image = $media_storage->load(9);
    if ($media_image instanceof MediaInterface) {
      return $media_image->get('field_media_image')->entity->get('uri')->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHeroVideo() {
    /**
     * @var \Drupal\media\MediaStorage $media_storage
     */
    $media_storage = $this->getEntityTypeManager()->getStorage('media');
    $media_video = $media_storage->load(10);
    if ($media_video instanceof MediaInterface) {
      return [
        'video/mp4' => $media_video->get('field_media_video_file')->entity->get('uri')->value,
      ];
    }

  }

}
