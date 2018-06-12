<?php

namespace Drupal\app_helper_libs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\node\Entity\NodeType;

/**
 * Configure example settings for this site.
 */
class BlogFilterContentTypeSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blog_filter_content_type_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'app_helper_libs.settings',
    ];
  }

 /**
   * {@inheritdoc}
   * Returns the topics choosen based on the language selected
   */

  public function getContentTypesByLang($selected) {

    $config = \Drupal::config('app_helper_libs.settings');
    $data = $config->get('blog_content_type_by_lang');
    return $data[$selected];

  }


  /**
   * {@inheritdoc}
   * Renders form for choosing content type mapping for each language
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
   $form = parent::buildForm($form, $form_state);
    $input = &$form_state->getUserInput();    
    $config = $this->config('app_helper_libs.settings');

    $language = \Drupal::languageManager()->getLanguages();    
    foreach ($language as $lang) {
      $langOpt[$lang->getId()] = $lang->getName();
    }
    $lang = isset($lang) ? $lang : 'en';
    
    $selected = !empty($form_state->getValue('language')) ? $form_state->getValue('language') : 'en';

    $form['language'] = array(
    '#type' => 'select',
    '#title' => 'Select Language',
    '#description' => 'Please select the language for Mapping the Content Types',
    '#options' => $langOpt,
    '#ajax' => array (
        'callback' => '::ajaxcallback',
        'effect' => 'fade',
        'event' => 'change',
        'wrapper' => 'select-filter-content-type',
        'progress' => array(
             'type' => 'throbber',
             'message' => 'loading',
         ),
      ),
    );
    $defaultArr = $config->get('blog_content_type_by_lang.'.$selected); 
    $form['content_type_multiselect'] = array(
      '#prefix' => '<div id = "select-filter-content-type">',
      '#suffix' => '</div>',
      '#type' => 'multiselect',
      '#size' => 40,
      '#name' => 'content_type_multiselect[]',
      '#options' => $this->getAllContentTypes($selected),
      '#title' => $this->t('Select popular topic to show in the front end'),
      '#attributes' => array('multiple' => 1, 'size' => 40),
      '#default_value' => array_keys($defaultArr),      
    ); 

    if (isset($input['_drupal_ajax']) && $input['_drupal_ajax'] == 1) {
      $input['content_type_multiselect'] = array_keys($defaultArr);
      $form_state->setUserInput($input);
    }

    return $form;
  }


  

  /**
   * {@inheritdoc}
   * Returns all the content types for mapping with language
   */

  public function getAllContentTypes($selected) {

    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }

    asort($contentTypesList);
    return $contentTypesList;

  }

  
  /**
   * {@inheritdoc}
   * Ajax callback function to render the multiselect element in the form
   */
  public function ajaxcallback(array &$form, FormStateInterface $form_state) {

    return $form['content_type_multiselect'];

  }


  /**
   * {@inheritdoc}
   * Handles SeeAll Topics form submission
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the configuration
    $lang = $form_state->getValue('language'); //$this->getLanguage();
    $config = $this->config('app_helper_libs.settings');
    $all_content_types = [];
	foreach (NodeType::loadMultiple() as $type => $content_type) {
		$all_content_types[$type] = $content_type->label();
	}
    $formArr = isset($_POST['content_type_multiselect']) ? ($_POST['content_type_multiselect']) : array();
    $formCnt = count($formArr);
    $defaultArr = array();
    for($i=0; $i < $formCnt; $i++) {
      $key =  $formArr[$i];
      $defaultArr[$key] = $all_content_types[$key];
    }

    $this->config('app_helper_libs.settings')
         ->set('blog_content_type_by_lang.'.$lang, $defaultArr)
         ->save();

    parent::submitForm($form, $form_state);

  }

 
}
