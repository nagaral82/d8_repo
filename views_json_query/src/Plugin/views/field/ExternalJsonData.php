<?php

namespace Drupal\views_json_query\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Class Avatar
 *
 * @ViewsField("external_json_data")
 */
class ExternalJsonData extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['key'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['key'] = array(
      '#title' => t('Key Chooser'),
      '#description' => t('choose a key'),
      '#type' => 'textfield',
      '#default_value' => $this->options['key'],
      '#required' => TRUE,
    );
    parent::buildOptionsForm($form, $form_state);
  }
	/**
   * {@inheritdoc}
   */
   public function render(ResultRow $values) {
	$value = $this->getValue($values);
    return $value;
  }
  
  /**
   * Called to add the field to a query.
   */
  public function query() {
    // Add the field.
    $this->table_alias = 'json';

    $this->field_alias = $this->query->add_field(
      $this->table_alias,
      $this->options['key'],
      '',
      $this->options
    );
  }
}