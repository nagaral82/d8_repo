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
 * @ViewsField("app_gallery_handler")
 */
class appGalleryHandler extends FieldPluginBase {

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
    $node = $values->_entity;
	$output = '';
	$field = 'field_app_media_image_attachmen';
	$imagesArrList = array();
	if (isset($node->get($field)->entity)) {
		$imagesArrList = array();
		$fileEntities = $node->get($field)->referencedEntities();
		foreach ($fileEntities  as $key => $mediaEntity) {
			$imgParams = array();
			$file = $mediaEntity->field_media_image_attachment->entity;
			$imgParams['image_path'] = file_create_url($file->getFileUri());
			$imagesArrList[] = $imgParams;
		}
		//drupal_set_message(print_r($node->id(), TRUE));
		$display = [
		  '#theme' => 'imagesliderkey',
		  '#images' => $imagesArrList,
		  '#cache' => ['max-age' => 0]
		];
		$output = render($display);
	}
	return $output;
  }
}