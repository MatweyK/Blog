<?php

namespace Drupal\tvi\Service;

use Drupal\taxonomy\TermInterface;

/**
 * Define an interface for returning a view assigned to a taxonomy term or
 * vocabulary.
 *
 *
 */
interface TaxonomyViewsIntegratorManagerInterface {

  /**
   * Return the taxonomy term View per taxonomy view integrator settings.
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   * @return array
   */
  public function getTaxonomyTermView(TermInterface $taxonomy_term);

  /**
   * Return array with view and display id for current term based on settings.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   An object with term entity.
   *
   * @return array
   *   An array with view_id and display_id for current term.
   */
  public function getTaxonomyTermViewAndDisplayId(TermInterface $taxonomy_term);

}
