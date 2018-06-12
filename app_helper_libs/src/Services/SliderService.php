<?php

namespace Drupal\app_helper_libs\Services;

/**
 * Helper functions for layerslider service.
 */
class SliderService {
	/**
	* Get the get media details by media ID as $mediaId params
	*
	* @param $mediaId
	*
	*/
	public function getMediaDetails($mediaId, $langcode='en') {
      $urls = [];
	  if ($mediaId) {
		  $query = db_select('file_managed', 'fm');
		  $query->fields('fm', ['uri']);
		  $query->join('media__field_media_image_attachment', 'mfmia', 'mfmia.field_media_image_attachment_target_id = fm.fid');
		  $query->join('media__field_media_post_id', 'mfmpi', 'mfmpi.entity_id = mfmia.entity_id');
		  $query->condition('mfmpi.field_media_post_id_value', $mediaId);
		  $query->condition('mfmpi.langcode', $langcode);
		  $result = $query->execute();
		  foreach ($result as $record) {
			$urls = ['furi' => $record->uri];
		  }
	  }
      return $urls ? $urls : NULL;
    }

    /**
	* Get the get layer slider images list by layer slider ID as $layerSliderId params
	*
	* @param $layerSliderId
	*
	*/
	public function getLayerSliderImagesList($layerSliderId, $langcode='en') {
    	$imagesArrList = array();
    	//$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    	if(!empty($layerSliderId)){
    		$query = \Drupal::entityQuery('node');
    		$query->condition('field_app_layer_slider_id', $layerSliderId);
    		$query->condition('type', 'app_layer_slider');
    		$query->condition('langcode', $langcode);
    		$query->range(0, 1);
    		$entity_ids = $query->execute();
    		$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
    		foreach ($nodes as $key => $node) {
    			$layerProperties = $node->get('field_app_layer_slider_data')->value;
    		}
    		$layerPropertiesValues = json_decode($layerProperties);
    		$imgParams = array();
    		foreach ($layerPropertiesValues->layers as $k => $v) {
    			$fImage = $this->getMediaDetails($v->properties->backgroundId, $langcode);
    			if(!empty($fImage)) {
	    			$imgParams['bg'] = file_create_url($fImage['furi']);
					if(isset($v->sublayers)){
		    			foreach ($v->sublayers as $k1 => $v1) {
							if($v1->media == "img"){
								$fSubImage = $this->getMediaDetails($v1->imageId, $langcode);
								if(!empty($fSubImage))
									$imgParams['image'] = file_create_url($fSubImage['furi']);
							}
		    			}
					}
	    			$imagesArrList['layer_images'][] = $imgParams;
    			} else {
    				/*
    				 * Bug fixing for WC-1570
    				 */
    				if(isset($v->sublayers)){
    					$html = '';
    					foreach ($v->sublayers as $k1 => $v1) {
    						if($v1->media == "img"){
    							$fSubImage = $this->getMediaDetails($v1->imageId, $langcode);
    							if(!empty($fSubImage))
    								$imgParams['image'] = file_create_url($fSubImage['furi']);
    								$imgParams['bg'] = $imgParams['image'];
    						}

    						/*
    						 * Caption handle post MVP
    						 */

    						/*
    						if($v1->html != ""){
    							$imgParams['caption'] = $v1->html;
    						}*/
    					}
    				}
    				$imagesArrList['layer_images'][] = $imgParams;
    			}
    		}
    	}
    	return $imagesArrList;
    }
}
