<?php

namespace Drupal\app_helper_libs\Plugin\UiPatterns\Pattern;

use Drupal\ui_patterns\UiPatternBase;
use Drupal\ui_patterns\UiPatternInterface;

/**
 * The UI Pattern plugin.
 *
 * ID is set to "yaml" for backward compatibility reasons.
 *
 * @UiPattern(
 *   id = "yaml",
 *   label = @Translation("Library Pattern"),
 *   description = @Translation("Pattern defined using a YAML file."),
 *   deriver = "\Drupal\ui_patterns_library\Plugin\Deriver\LibraryDeriver"
 * )
 */
class LibraryPattern extends UiPatternBase implements UiPatternInterface {
  /**
   * {@inheritdoc}
   */
  public function getFieldsAsOptions() {
    $return = array();
	foreach ($this->getFields() as $fields) {
		$return[$fields['name']] = $fields['label'];
	}
	return $return;
	
  }
}
