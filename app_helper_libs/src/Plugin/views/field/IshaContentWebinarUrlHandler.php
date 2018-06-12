<?php

/**
 * @file
 * Definition of Drupal\app_helper_libs\Plugin\views\field\appContentWebinarUrlHandler
 */

namespace Drupal\app_helper_libs\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\image\Entity\ImageStyle;
use Drupal\media_entity\Entity\Media;
use Drupal\Core\Url;

/**
 * Field handler to display the content webinar url.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("app_content_webinar_url_handler")
 */
class appContentWebinarUrlHandler extends FieldPluginBase {

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
	$link_url = '';

	if ($entity->get('field_is_redirect_url')->value  == 'Yes') {
		$mylink = Url::fromUri($entity->get('field_sitewide_link')->uri, array('absolute' => true));
		$redirect_encode_url = urlencode($mylink->toString()) ;
		$url = Url::fromRoute('app_autologin.joinwebinar',array(), array('absolute' => true, 'query' => ['redirect_url'=> $redirect_encode_url, 'title'=>$entity->getTitle()]));
		$link_url = $url->toString();
		//$link_url .= '?redirect_url='.$redirect_encode_url.'&title='.$entity->getTitle();
	}/**/
	return $link_url;
  }
}
