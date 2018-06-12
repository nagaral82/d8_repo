<?php
namespace Drupal\app_helper_libs\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "tags_entity_reference_formatter",
 *   label = @Translation("Tags Formatter"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceLabelFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['link'] = [
      '#title' => t('Link label to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('link') ? t('Link to the referenced entity') : t('No link');
    return $summary;
  }

  public function searchTopicsById($array, $key, $paramName) {
    $results = [];
    if(!empty($array)) {
      foreach ($array as $k => $v) {
        if(array_key_exists($key, $v)) {
          $results['param'] = $paramName;
          $results['lang'] = $k;
          break;
        }
      }
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_as_link = $this->getSetting('link');

    $config = \Drupal::service('config.factory')->getEditable('app_helper_libs.settings');
    $popularTopicsArr = $config->get('popular_topic');
    $allTopicsArr = $config->get('see_all_popular_topic');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $topicQueryParams = "";

      $topicQueryParams = $this->searchTopicsById($popularTopicsArr, $entity->id(), 'topic');
      if(empty($topicQueryParams)) {
        $topicQueryParams = $this->searchTopicsById($allTopicsArr, $entity->id(), 'all_topics');
      }
      if(empty($topicQueryParams)) {
        $topicQueryParams = array("param"=>"other_topics", "lang"=> $langcode);
      }
         $label = $entity->label();        
         $term_name = \Drupal::service('pathauto.alias_cleaner')->cleanString($label);
         //drupal_set_message(print_r($term_name, TRUE));
         if (!empty($term_name)) {
           //$final_url = Url::fromUserInput('/wisdom/topic/' . $term_name, array("absolute" => TRUE));
           $final_url = Url::fromRoute('wisdom.display_content_with_filters', array("paramkey" => "topic", "paramdata" => $term_name), array("absolute" => TRUE));
         }
         
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$entity->isNew()) {
        try {
          $uri = $entity->urlInfo();
        }
        catch (UndefinedLinkTemplateException $e) {
          // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
          // and it means that the entity type doesn't have a link template nor
          // a valid "uri_callback", so don't bother trying to output a link for
          // the rest of the referenced entities.
          $output_as_link = FALSE;
        }
      }
        
      //drupal_set_message(print_r($final_url, TRUE)); 
      
      if ($output_as_link && isset($uri) && !$entity->isNew()) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $label,
          '#url' => $final_url,
          '#options' => $uri->getOptions(),
        ];

        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += ['attributes' => []];
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = ['#plain_text' => $label];
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }

}
