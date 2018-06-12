<?php

namespace Drupal\app_helper_libs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\taxonomy\Entity\Term;

/**
 * Configure example settings for this site.
 */
class SeeAllPopularTopicSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'see_all_popular_topic_admin_settings';
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
   * Form render for SeeAll Topics
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
    '#description' => 'Please select the language for choosing the Topics',
    '#options' => $langOpt,
    '#ajax' => array (
        'callback' => '::ajaxcallback',
        'effect' => 'fade',
        'event' => 'change',
        'wrapper' => 'select-seeall-topics',
        'progress' => array(
             'type' => 'throbber',
             'message' => 'loading',
         ),
      ),
    );
    
	$defaultArr = $config->get('see_all_popular_topic.'.$selected); 
    $form['see_all_popular_topic_multiselect'] = array(
      '#prefix' => '<div id = "select-seeall-topics">',
      '#suffix' => '</div>',
      '#type' => 'multiselect',
      '#size' => 40,
      '#name' => 'see_all_popular_topic_multiselect[]',
      '#options' => $this->getTopicsByLang($selected),
      '#title' => $this->t('Select popular topic to show in the front end'),
      '#attributes' => array('multiple' => 1, 'size' => 40),
      '#default_value' => array_keys($defaultArr),      
    ); 

    if (isset($input['_drupal_ajax']) && $input['_drupal_ajax'] == 1) {
      $input['see_all_popular_topic_multiselect'] = array_keys($defaultArr);
      $form_state->setUserInput($input);
    }

    return $form;
  }


  /**
   * {@inheritdoc}
   * Returns the topics choosen based on the language selected
   */

  public function getDefaultTopics($selected) {

    $config = \Drupal::config('app_helper_libs.settings');
    $data = $config->get('see_all_popular_topic');
    return $data[$selected];

  }


  /**
   * {@inheritdoc}
   * Returns all the topics for the specified language code
   */

  public function getTopicsByLang($selected) {
	$service = \Drupal::service('app_helper_libs.term_helper');
	$conditional_fields = [ 
		'vid' => 'app_tags',
		'langcode' => $selected,
	];
	$termObjects = $service->getTermByCondition($conditional_fields);
	foreach ($termObjects as $tid => $termObject) {
		$optionArr[$tid] = t($termObject->get('name')->value);
	}
	/*
    $query = db_select('taxonomy_term_field_data', 'tfd');
    $query->condition('vid','app_tags');

    //To list the tags from all languages
    $query->condition('langcode', $selected);

    $query->fields('tfd', array('tid', 'name', 'weight', 'langcode'));
    $query->orderBy('weight', 'ASC');
    $query->orderBy('name', 'ASC');
    $termObj = $query->execute();

    $optionArr = array();
    while($row = $termObj->fetchAssoc()) {
      $optionArr[$row['tid']] = $this->t($row['name']);
      //drupal_set_message('<pre>'.print_r(time(), TRUE).'</pre>');
    }*/
	
    return $optionArr;   

  }

  
  /**
   * {@inheritdoc}
   * Ajax callback function to render the multiselect element in the form
   */
  public function ajaxcallback(array &$form, FormStateInterface $form_state) {

    return $form['see_all_popular_topic_multiselect'];

  }


  /**
   * {@inheritdoc}
   * Handles SeeAll Topics form submission
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the configuration
    $lang = $form_state->getValue('language'); //$this->getLanguage();
    $config = $this->config('app_helper_libs.settings');
    

    $formArr = isset($_POST['see_all_popular_topic_multiselect']) ? ($_POST['see_all_popular_topic_multiselect']) : array();
    $formCnt = count($formArr);
    $defaultArr = array();
    for($i=0; $i < $formCnt; $i++) {
      $key = 	$formArr[$i];
	  $term = Term::load($key);
	  $defaultArr[$key] = t($term->get('name')->value);
    }

    /*
    * Unset the popular topic if sell all topic itself unset
    */
    $popularTopicArr = $config->get('popular_topic.'.$lang);
    if (!is_array($popularTopicArr)) {
      $popularTopicArr = array();
    }

    foreach($popularTopicArr as $key => $value) {
    if (!isset($defaultArr[$key])) {
      unset($popularTopicArr[$key]);
    }
    }

    $this->config('app_helper_libs.settings')
         ->set('see_all_popular_topic.'.$lang, $defaultArr)
         ->set('popular_topic.'.$lang, $popularTopicArr)
         ->save();

    parent::submitForm($form, $form_state);

  }

 
}
