<?php

/**
 * @file
 * Main file for all custom theme hooks preprocess.
 */

/**
 * Implements template_preprocess_HOOK() for blog-hero.html.twig.
 */
function template_preprocess_blog_hero(&$variables) {
  // Image is required for video. It will be used for poster.
  if (!empty($variables['video'])) {
    if (!$variables['image']) {
      $variables['video'] = [];
    }
  }
}
