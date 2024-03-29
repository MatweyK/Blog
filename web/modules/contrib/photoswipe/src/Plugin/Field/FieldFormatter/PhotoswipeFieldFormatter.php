<?php

namespace Drupal\photoswipe\Plugin\Field\FieldFormatter;


use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\MediaType;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'photoswipe_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "photoswipe_field_formatter",
 *   label = @Translation("Photoswipe"),
 *   field_types = {
 *     "entity_reference",
 *     "image",
 *     "link",
 *     "string",
 *     "string_long"
 *   }
 * )
 */
class PhotoswipeFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The oEmbed resource fetcher.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * The oEmbed URL resolver service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The media settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * Constructs an OEmbedFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, IFrameUrlHelper $iframe_url_helper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->messenger = $messenger;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->logger = $logger_factory->get('media');
    $this->config = $config_factory->get('media.settings');
    $this->iFrameUrlHelper = $iframe_url_helper;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('messenger'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('media.oembed.iframe_url_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'photoswipe_node_style_first' => '',
      'photoswipe_node_style' => '',
      'photoswipe_image_style' => '',
      'photoswipe_reference_image_field' => '',
      'photoswipe_caption' => '',
      'photoswipe_caption_custom' => '',
      'photoswipe_view_mode' => '',
      'max_width' => 0,
      'max_height' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $image_styles_hide = $image_styles;
    $image_styles_hide['hide'] = $this->t('Hide (do not display image)');
    $element['photoswipe_node_style_first'] = [
      '#title' => $this->t('Node image style for first image'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_node_style_first'),
      '#empty_option' => $this->t('No special style.'),
      '#options' => $image_styles_hide,
      '#description' => $this->t('Image style to use in the content for the first image.'),
    ];
    $element['photoswipe_node_style'] = [
      '#title' => $this->t('Node image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_node_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles_hide,
      '#description' => $this->t('Image style to use in the node.'),
    ];
    $element['photoswipe_image_style'] = [
      '#title' => $this->t('Photoswipe image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $this->t('Image style to use in the Photoswipe.'),
    ];

    // Set our caption options.
    $caption_options = [
      'title' => $this->t('Image title tag'),
      'alt' => $this->t('Image alt tag'),
      'node_title' => $this->t('Entity title'),
      'custom' => $this->t('Custom (with tokens)'),
    ];

    $element = $this->addEntityReferenceSettings($element);

    // Add the other parent entity fields as options.
    if (isset($form['#fields'])) {
      foreach ($form['#fields'] as $parent_field) {
        if ($parent_field != $this->fieldDefinition->getName()) {
          $caption_options[$parent_field] = $parent_field;
        }
      }
    }

    $element['photoswipe_caption'] = [
      '#title' => $this->t('Photoswipe image caption'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_caption'),
      '#options' => $caption_options,
      '#description' => $this->t('Field that should be used for the caption.'),
    ];

    $element['photoswipe_caption_custom'] = [
      '#title' => $this->t('Custom caption'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('photoswipe_caption_custom'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][photoswipe_caption]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['photoswipe_token_caption'] = [
        '#type' => 'fieldset',
        '#title' => t('Replacement patterns'),
        '#theme' => 'token_tree_link',
        // A KLUDGE! Need to figure out current entity type.
        // in both entity display and views contexts.
        '#token_types' => ['file', 'node'],
        '#states' => [
          'visible' => [
            ':input[name$="[settings][photoswipe_caption]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }
    else {
      $element['photoswipe_token_caption'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#description' => '<strong class="error">' . $this->t('For token support the <a href="@token_url">token module</a> must be installed.', ['@token_url' => 'http://drupal.org/project/token']) . '</strong>',
        '#states' => [
          'visible' => [
            ':input[name$="[settings][photoswipe_caption]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }

    // Add the current view mode so we can control view mode for node fields.
    $element['photoswipe_view_mode'] = [
      '#type' => 'hidden',
      '#value' => $this->viewMode,
    ];

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * Adds extra settings related when dealing with an entity reference.
   *
   * @param array $element
   *   The settings form structure of this formatter.
   *
   * @return array
   *   The modified settings form structure of this formatter.
   */
  private function addEntityReferenceSettings(array $element) {

    if ($this->fieldDefinition->getType() !== 'entity_reference') {
      return $element;
    }
      $target_type = $this->fieldDefinition->getSetting('target_type');
      $target_bundles = $this->fieldDefinition->getSetting('handler_settings')['target_bundles'];

      /* @var $fields FieldDefinitionInterface[] */
      $fields = [];
      foreach ($target_bundles as $bundle) {
        $fields += \Drupal::service('entity_field.manager')
          ->getFieldDefinitions($target_type, $bundle);
      }
      $fields = array_filter($fields, function (FieldDefinitionInterface $field) {
        return $field->getType() === 'image' && $field->getName() !== 'thumbnail';
      });

      $field_options = [];
      foreach ($fields as $name => $field) {
        $field_options[$name] = $field->getName();
      }

      $element['photoswipe_reference_image_field'] = [
        '#title' => $this->t('Image field of the referenced entity'),
        '#type' => 'select',
        '#default_value' => $this->getSetting('photoswipe_reference_image_field'),
        '#options' => $field_options,
        '#description' => $this->t('Field that contains the image to be used.'),
      ];
      return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$this->getSetting('photoswipe_node_style')])) {
      $summary[] = $this->t('Node image style: @style', ['@style' => $image_styles[$this->getSetting('photoswipe_node_style')]]);
    }
    elseif ($this->getSetting('photoswipe_node_style') == 'hide') {
      $summary[] = $this->t('Node image style: Hide');
    }
    else {
      $summary[] = $this->t('Node image style: Original image');
    }

    if (isset($image_styles[$this->getSetting('photoswipe_node_style_first')])) {
      $summary[] = $this->t('Node image style of first image: @style', ['@style' => $image_styles[$this->getSetting('photoswipe_node_style_first')]]);
    }
    elseif ($this->getSetting('photoswipe_node_style_first') == 'hide') {
      $summary[] = $this->t('Node image style of first image: Hide');
    }
    else {
      $summary[] = $this->t('Node image style of first image: Original image');
    }

    if (isset($image_styles[$this->getSetting('photoswipe_image_style')])) {
      $summary[] = $this->t('Photoswipe image style: @style', ['@style' => $image_styles[$this->getSetting('photoswipe_image_style')]]);
    }
    else {
      $summary[] = $this->t('photoswipe image style: Original image');
    }

    if ($this->getSetting('photoswipe_reference_image_field')) {
      $summary[] = $this->t('Referenced entity image field: @field', ['@field' => $this->getSetting('photoswipe_reference_image_field')]);
    }

    if ($this->getSetting('photoswipe_caption')) {
      $caption_options = [
        'alt' => $this->t('Image alt tag'),
        'title' => $this->t('Image title tag'),
        'node_title' => $this->t('Entity title'),
        'custom' => $this->t('Custom (with tokens)'),
      ];
      if (array_key_exists($this->getSetting('photoswipe_caption'), $caption_options)) {
        $caption_setting = $caption_options[$this->getSetting('photoswipe_caption')];
      }
      else {
        $caption_setting = $this->getSetting('photoswipe_caption');
      }
      $summary[] = $this->t('Photoswipe Caption: @field', ['@field' => $caption_setting]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    if ($items->isEmpty()) {
      $default_image = $this->getFieldSetting('default_image');
      // If we are dealing with a configurable field, look in both
      // instance-level and field-level settings.
      if (empty($default_image['uuid']) && $this->fieldDefinition instanceof FieldConfigInterface) {
        $default_image = $this->fieldDefinition->getFieldStorageDefinition()
          ->getSetting('default_image');
      }
      if (!empty($default_image['uuid']) && $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_image['uuid'])) {
        // Clone the FieldItemList into a runtime-only object for the formatter,
        // so that the fallback image can be rendered without affecting the
        // field values in the entity being rendered.
        $items = clone $items;
        $items->setValue([
          'target_id' => $file->id(),
          'alt' => $default_image['alt'],
          'title' => $default_image['title'],
          'width' => $default_image['width'],
          'height' => $default_image['height'],
          'entity' => $file,
          '_loaded' => TRUE,
          '_is_default' => TRUE,
        ]);
      }
    }

    \Drupal::service('photoswipe.assets_manager')->attach($elements);
    if (!empty($items) && count($items) > 1) {
      // If there are more than 1 elements, add the gallery wrapper.
      // Otherwise this is done in javascript for more flexibility.
      $elements['#prefix'] = '<div class="photoswipe-gallery">';
      $elements['#suffix'] = '</div>';
    }

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'photoswipe_image_formatter',
        '#item' => $item,
        '#entity' => $items->getEntity(),
        '#display_settings' => $settings,
        '#delta' => $delta,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_ids = [];
    $style_ids[] = $this->getSetting('photoswipe_node_style');
    if (!empty($this->getSetting('photoswipe_node_style_first'))) {
      $style_ids[] = $this->getSetting('photoswipe_node_style_first');
    }
    $style_ids[] = $this->getSetting('photoswipe_image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    foreach ($style_ids as $style_id) {
      if ($style_id && $style = ImageStyle::load($style_id)) {
        // If this formatter uses a valid image style to display the image, add
        // the image style configuration entity as dependency of this formatter.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_ids = [];
    $style_ids['photoswipe_node_style'] = $this->getSetting('photoswipe_node_style');
    if (!empty($this->getSetting('photoswipe_node_style_first'))) {
      $style_ids['photoswipe_node_style_first'] = $this->getSetting('photoswipe_node_style_first');
    }
    $style_ids['photoswipe_image_style'] = $this->getSetting('photoswipe_image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    foreach ($style_ids as $name => $style_id) {
      if ($style_id && $style = ImageStyle::load($style_id)) {
        if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
          $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
          // If a valid replacement has been provided in the storage, replace
          // the image style with the replacement and signal that the formatter
          // plugin settings were updated.
          if ($replacement_id && ImageStyle::load($replacement_id)) {
            $this->setSetting($name, $replacement_id);
            $changed = TRUE;
          }
        }
      }
    }
    return $changed;
  }

  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }
    if (parent::isApplicable($field_definition)) {
      $media_type = $field_definition->getTargetBundle();
      if ($media_type) {
        $media_type = MediaType::load($media_type);
      }
      if ($field_definition->getType() == 'image' || $media_type && $media_type->getSource() instanceof OEmbedInterface) {
        return TRUE;
      };
    }
    return FALSE;
  }
}
