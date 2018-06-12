<?php

namespace Drupal\app_helper_libs\Services;

use Drupal\image\Entity\ImageStyle;
use Drupal\media_entity\Entity\Media;
use Drupal\Core\Url;


/**
 * Helper functions for layerslider service.
 */
class YoutubeThumbnailService {
	/**
	* Get the get media details by media ID as $mediaId params
	*
	* @param $mediaId
	* 
	*/
	public function getThumbnailImage($node, $image_style = 'wisdom_grid_image_style') {
	$uri = '';
	if ($node->field_app_media_image_attachmen->target_id) {
		$media_entity = Media::load($node->field_app_media_image_attachmen->target_id);
		$file = $media_entity->field_media_image_attachment->entity;
		$uri = ($file) ? $file->getFileUri() : '';
	}
	else {
	  $provider = \Drupal::service('video_embed_field.provider_manager')->loadProviderFromInput($node->field_video_embed_url->value);
	  if ($provider) {
		$provider->downloadThumbnail();
		$uri = $provider->getLocalThumbnailUri();
	  }
	}
	$image_uri = $uri ? ImageStyle::load($image_style)->buildUrl($uri) : '';
	
		if($node->bundle() == 'app_videos') {
			$output = [
				'#theme' => 'image',
				'#prefix' => "<span></span>",
				'#uri' => $image_uri,
			];
		}
		else {
			$output = [
				'#theme' => 'image',
				'#uri' => $image_uri,
			];
		}
		
		
		return $output;
	}
}
