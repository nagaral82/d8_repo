<?php

namespace Drupal\app_helper_libs\Services;
use \Drupal\taxonomy\Entity\Term;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Helper functions for taxonomy service.
 */
class TaxonomyService {
	
	/**
	* Get the taxonomy term id from cache if exists otherwise create a new term object.
	*
	* @param $vid
	*   Vocabulary id - 'tags' or 'category'.
	* @param $name
	*   Term name - 'video' or 'podcast'.
	* @return \Drupal\taxonomy\Entity\Term object
	*/
	public function getTermByVocabulary($vid, $name) {
		$cid = 'cache_'.$vid.'_'.$name;
		$language = get_content_migration_default_value();
		$taxonomyObject = '';
		$cache = \Drupal::cache()->get($cid);
		if (isset($cache->data) && $cache->data) {
			$taxonomyObject = $cache->data;
		}
		else {
			$taxonomyObject = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name, 'vid' => $vid]);
			if ($taxonomyObject) {
				$taxonomyObject = reset($taxonomyObject);
        
			}
			else {
				$taxonomyObject = Term::create([
				  'vid' => $vid,
				  'name' => $name,
				  'langcode' => $language,
				]);
				$taxonomyObject->save();
        
			}
			// Set the taxonomy object in cache
			\Drupal::cache()->set($cid, $taxonomyObject, CacheBackendInterface::CACHE_PERMANENT, array());
		}
		return $taxonomyObject;
	}
	
	/**
	* Returns the first field referencing a given vocabulary.
	*
	* @param string $bundle
	* @param string $vocabulary
	* @return string
	*/
	public function getTermField($bundle, $vocabulary) {
		$all_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $bundle);
		foreach ($all_fields as $field_name => $field_definition) {
		  if ($field_definition->getType() == 'entity_reference') {
			$storage = $field_definition->getFieldStorageDefinition();
			if ($storage->getSetting('target_type') == 'taxonomy_term') {
			  $handler_settings = $field_definition->getSetting('handler_settings');
			  if (isset($handler_settings['target_bundles'][$vocabulary])) {
				return $field_name;
			  }
			}
		  }
		}
		return '';
	}
	
	/**
	* Get the taxonomy term id from cache if exists otherwise create a new term object.
	*
	* @param $conditional_fields
	*   Set of conditional_fields in the form of key value pairs
	* @return \Drupal\taxonomy\Entity\Term object
	*/
	public function getTermByCondition($conditional_fields) {
		$taxonomyObject = '';
		if ($conditional_fields) {
			$cache_ids = [];
			$is_cache = TRUE;
			foreach($conditional_fields as $key => $data) {
				if (is_array($data)) {
					$is_cache = FALSE;
					break;
				}
				$cache_ids[$key] = $data;
			}
			$cid = implode("_", $cache_ids);
			$cache = \Drupal::cache()->get($cid);
			if ($is_cache && isset($cache->data) && $cache->data) {
				$taxonomyObject = $cache->data;
			}
			else {
				$taxonomyObject = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($conditional_fields);
				\Drupal::cache()->set($cid, $taxonomyObject, CacheBackendInterface::CACHE_PERMANENT, array('term:getTermByCondition'));
			}
		}
		return $taxonomyObject;
	}
	/**
	* Get the clean taxonomy strings using vocubulary id
	*
	* @param $conditional_fields
	*   Set of conditional_fields in the form of key value pairs
	* @return \Drupal\taxonomy\Entity\Term object
	*/
	public function getCleanTaxonomy($vid) {
		$cleanTaxonomy = [];
		$cid = 'getCleanTaxonomy_'.$vid;
		$cache = \Drupal::cache()->get($cid);
		if (isset($cache->data) && $cache->data) {
			$cleanTaxonomy = $cache->data;
		}
		else {
			$query = db_select('taxonomy_term_field_data', 'term');
			$query->fields('term', ['name', 'tid']);
			$query->condition('term.vid', $vid);
			$result = $query->execute();
			foreach ($result as $record) {
				$term_name = \Drupal::service('pathauto.alias_cleaner')->cleanString($record->name);
				$cleanTaxonomy[$term_name] = $record->tid;
			}
			\Drupal::cache()->set($cid, $cleanTaxonomy, CacheBackendInterface::CACHE_PERMANENT, array('term:getCleanTaxonomy'));
		}
		return $cleanTaxonomy;
	}
}
