<?php

/**
 * @file
 * Definition of Drupal\app_helper_libs\Plugin\views\field\appImageAttachmentHandler
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
 * @ViewsField("app_image_attachment_handler")
 */
class appImageAttachmentHandler extends FieldPluginBase {

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
    $options['view_type'] = array('default' => 'web');
	$options['image_style'] = array('default' => '');
	$options['link_to_entity'] = array('default' => '');

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['view_type'] = array(
      '#title' => $this->t('Which view type should be rendered?'),
      '#type' => 'select',
      '#default_value' => $this->options['view_type'],
      '#options' => ['web' => $this->t('Web'), 'rest_api' => $this->t('Rest API')],
    );
    $form['image_style'] = [
      '#title' => $this->t('Image Style'),
      '#type' => 'select',
      '#default_value' => $this->options['image_style'],
      '#required' => FALSE,
      '#options' => image_style_options(),
    ];
	$form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the original content'),
      '#default_value' => $this->options['link_to_entity'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
	$uri = '';
	$alt = '';
	$title = '';
	if ($node->field_app_media_image_attachmen->target_id) {
		$media_entity = Media::load($node->field_app_media_image_attachmen->target_id);
		$field_media_image_attachment = $media_entity->field_media_image_attachment;
		$file = $field_media_image_attachment->entity;
		$uri = ($file) ? $file->getFileUri() : '';
		$alt = ($field_media_image_attachment) ? $field_media_image_attachment->alt : '';
		$title = ($field_media_image_attachment) ? $field_media_image_attachment->title : '';
	}
	else {
	  $provider = \Drupal::service('video_embed_field.provider_manager')->loadProviderFromInput($node->field_video_embed_url->value);
	  if ($provider) {
		$provider->downloadThumbnail();
		$uri = $provider->getLocalThumbnailUri();
	  }
	}
	$image_uri = $uri ? (($this->options['image_style']) ? ImageStyle::load($this->options['image_style'])->buildUrl($uri) : file_create_url($uri)) : '';
	if ($this->options['view_type'] == 'rest_api') {
		return $image_uri;
	}
	else {
		if($node->bundle() == 'app_videos') {
			$output = [
				'#theme' => 'image',
				'#prefix' => "<span></span>",
				'#uri' => $image_uri,
			];
			if(!empty($alt)) {
				$output['#alt'] = $alt;
			}
			if(!empty($title)) {
				$output['#title'] = $title;
			}
		}
		else {
			$output = [
				'#theme' => 'image',
				'#uri' => $image_uri
			];
			if(!empty($alt)) {
				$output['#alt'] = $alt;
			}
			if(!empty($title)) {
				$output['#title'] = $title;
			}
		}
		
		if ($this->options['link_to_entity']) {
			$original_link = Url::fromUri('internal:/node/'.$node->id());
			$output = [
				'#type' => 'link',
				'#title' => $output,
				'#url' => $original_link,
			];
		}
		return $output;
	}
  }
}