<?php

namespace Drupal\blog_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Html;
use Drupal\paragraphs\Annotation\ParagraphsBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * @ParagraphsBehavior(
 *   id = "blog_paragraphs_image_and_text",
 *   label = @Translation("Image and text settings settings"),
 *   description= @Translation("Settings for Image and text paragraphs type"),
 *   weight = 0,
 * )
  */
class ImageAndTextBehavior extends ParagraphsBehaviorBase {
  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type)
  {
    return $paragraphs_type->id() == 'image_and_text';
  }



  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state)
  {
    $form['image_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image position'),
      '#options' => [
        'left' => $this->t('left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('$this->getPluginId()', 'image_position', 'left'),
    ];
    $form['image_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Image size'),
      '#description' =>$this->t('Size of the image in grid.'),
      '#options' => [
        '4_12' => $this->t('4 columns of 12'),
        '6_12' => $this->t('6 columns of 12'),
        '8_12' => $this->t('8 columns of 12'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('$this->getPluginId()', 'image_size', '4_12'),
    ];

    return $form;
  }

  /**
   * Extends the paragraph render array with behavior.
   *
   * @param array &$build
   *   A renderable array representing the paragraph. The module may add
   *   elements to $build prior to rendering. The structure of $build is a
   *   renderable array as expected by drupal_render().
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options configured for the
   *   entity components.
   * @param string $view_mode
   *   The view mode the entity is rendered in.
   *
   * @return array
   *   A render array provided by the plugin.
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $bem_block = 'paragraph-' . $paragraph->bundle() . ($view_mode == 'default' ? '' : '-' . $view_mode);
    $image_position = $paragraph->getBehaviorSetting('$this->getPluginId()', 'image_position', 'left');
    $image_size = $paragraph->getBehaviorSetting('$this->getPluginId()', 'image_size', '4_12');

    $build['#attributes']['class'][] = \Drupal\Component\Utility\Html::getClass($bem_block . '--image-position-' . $image_position);
    $build['#attributes']['class'][] = \Drupal\Component\Utility\Html::getClass($bem_block . '--image-size-' . $image_size);

    if (isset($build['field_image']) && $build['field_image']['#formatter'] == 'media_thumbnail'){
      switch ($image_size){
        case '4_12':
          $image_style = 'paragraph_image_text_4_of_12';
          break;


        case '6_12' :
          $image_style = 'paragraph_image_text_6_of_12';
          break;

        case '8_12' :
          $image_style = 'paragraph_image_text_8_of_12';
          break;
      }

      $build['field_image'][0]['#image_style'];
    }
  }

}


