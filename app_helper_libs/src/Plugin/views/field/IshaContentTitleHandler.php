<?php

/**
 * @file
 * Definition of Drupal\app_helper_libs\Plugin\views\field\appGalleryHandler
 */

namespace Drupal\app_helper_libs\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\image\Entity\ImageStyle;
use Drupal\media_entity\Entity\Media;
use Drupal\Core\Url;

/**
 * Field handler to display the content image attachment.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("app_content_title_handler")
 */
class appContentTitleHandler extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
	$entity = $values->_entity;
	$content_title = $entity->getTitle();
	$langcode = isset($values->node_field_data_langcode) ? $values->node_field_data_langcode : 'en';		
	if ($langcode && $entity->hasTranslation($langcode)) {
	   $content_title = $entity->getTranslation($langcode)->getTitle();
    }
    return $content_title;
  }
}