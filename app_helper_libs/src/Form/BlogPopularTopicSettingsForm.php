<?php

namespace Drupal\app_helper_libs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\taxonomy\Entity\Term;

/**
 * Configure example settings for this site.
 */
class BlogPopularTopicSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blog_popular_topic_admin_settings';
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

  public function getDefaultTopics($selected) {

    $config = \Drupal::config('app_helper_libs.settings');
    $data = $config->get('blog_popular_topic');
    return $data[$selected];

  }


  /**
   * {@inheritdoc}
   * https://www.drupal.org/docs/7/api/database-api/dynamic-queries/conditional-clauses
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $input = &$form_state->getUserInput();    


    $language = \Drupal::languageManager()->getLanguages();    
    foreach ($language as $lang) {
      $langOpt[$lang->getId()] = $lang->getName();
    }
    $lang = isset($lang) ? $lang : 'en';

    $config = $this->config('app_helper_libs.settings');
    $selected = !empty($form_state->getValue('language')) ? $form_state->getValue('language') : 'en';
    $seeAllPopularTopic = $config->get('blog_see_all_popular_topic.'.$selected);

    $form['language'] = array(
    '#type' => 'select',
    '#title' => 'Select Language',
    '#description' => 'Please select the language for choosing the Topics',
    '#options' => $langOpt,
    '#ajax' => array (
        'callback' => '::ajaxcallback',
        'effect' => 'fade',
        'event' => 'change',
        'wrapper' => 'select-popular-topics',
        'progress' => array(
             'type' => 'throbber',
             'message' => 'loading',
         ),
      ),
    );

    
    if (!is_array($seeAllPopularTopic)) {
      $seeAllPopularTopic = array();
    }
   
    if (count($seeAllPopularTopic) >= 1) {
	  /*
      $query = db_select('taxonomy_term_field_data', 'tfd');
      $query->condition('vid','app_tags');
      $query->condition('langcode', $selected);
      $query->condition('tid', $seeAllPopularTopic, 'IN');
      $query->fields('tfd', array('tid', 'name', 'weight', 'langcode'));
      $query->orderBy('name', 'ASC');
      $termObj = $query->execute();
      $optionArr = array();
      while($row = $termObj->fetchAssoc()) {
        $optionArr[$row['tid']] = $this->t($row['name']);
      }
	*/  
	$service = \Drupal::service('app_helper_libs.term_helper');
	$conditional_fields = [ 
		'vid' => 'app_tags',
		'tid' => array_keys($seeAllPopularTopic),
		'langcode' => $selected,
	];
	$termObjects = $service->getTermByCondition($conditional_fields);
	foreach ($termObjects as $tid => $termObject) {
		$optionArr[$tid] = t($termObject->get('name')->value);
	}
     
     //Get the selected topics from Configuration
      $defaultArr = $config->get('blog_popular_topic.'.$selected); 

      $form['popular_topic_multiselect'] = array(
        '#prefix' => '<div id = "select-popular-topics">',
        '#suffix' => '</div>',
        '#type' => 'multiselect',
        '#name' => 'popular_topic_multiselect[]',
        '#options' => $optionArr,
        '#title' => $this->t('Select popular topic to show in the front end'),
        '#default_value' => array_keys($defaultArr),
        '#attributes' => array('multiple' => 1),
      );

      if (isset($input['_drupal_ajax']) && $input['_drupal_ajax'] == 1) {
        $input['popular_topic_multiselect'] = array_keys($defaultArr);
        $form_state->setUserInput($input);
      }

    } else {
        // Handles if SeeAllTopis is empty for the selected language
        $routname = 'apptags.blog_see_all_popular_topic_default_settings';
        $url = Url::fromRoute($routname);
        $seeallUrl = \Drupal::l(t('Blog See all topic'), $url);

        $form['popular_topic_multiselect'] = array(
                        '#type'=> 'item',
                        '#prefix' => '<div id = "select-popular-topics">',
                        '#suffix' => '</div>',
                        '#markup' => t('Please choose "'.$seeallUrl.'" for ' . $langOpt[$selected] . ' before selecting its "Popular topics"')
        );
    }
    return parent::buildForm($form, $form_state);

  }
  

  /**
   * {@inheritdoc}
   * Ajax callback function to render the multiselect element in the form
   */
  public function ajaxcallback(array &$form, FormStateInterface $form_state) {

    return $form['popular_topic_multiselect'];

  }



/**
 * {@inheritdoc}
 */
public function submitForm(array &$form, FormStateInterface $form_state) {

  // Retrieve the configuration
    $lang = $form_state->getValue('language'); //$this->getLanguage();
    $config = $this->config('app_helper_libs.settings');
    
  $formArr = isset($_POST['popular_topic_multiselect']) ? $_POST['popular_topic_multiselect'] : array();
  $formCnt = count($formArr);
  $defaultArr = array();
  for($i = 0;$i < $formCnt;$i++) {
    $key =  $formArr[$i];
	$term = Term::load($key);
    $defaultArr[$key] = $term->get('name')->value;
  }
  $this->config('app_helper_libs.settings')
       ->set('blog_popular_topic.'.$lang, $defaultArr)
       ->save();
  parent::submitForm($form, $form_state);
}

  
}
