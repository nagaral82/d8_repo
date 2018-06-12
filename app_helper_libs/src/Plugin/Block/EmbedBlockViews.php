<?php

namespace Drupal\app_helper_libs\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'EmbedBlockViews' block.
 *
 * @Block(
 *  id = "embed_views_block",
 *  admin_label = @Translation("Embed view blocks"),
 * )
 */
class EmbedBlockViews extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'embed_block_views' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['embed_block_views_configuration'] = array(
      '#type' => 'textfield',
	  '#required' => TRUE,
      '#title' => $this->t('Block Configuration'),
	  '#description' => $this->t('Specify the list of block names and display ids seperated by a new a pipe (|). Eg) views_name_1:block1|views_name_2:block1 etc'),
      '#default_value' => $this->configuration['embed_block_views_configuration'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
	$config = \Drupal::config('app_helper_libs.settings');
    $this->configuration['embed_block_views_configuration'] = $form_state->getValue('embed_block_views_configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
  $output = '';
  $build = [];
  $embed_block_views = [];
  
 // print_r($this->label());exit;
  if (isset($this->configuration['embed_block_views_configuration'])) {
	$embed_block_views = explode("|", trim($this->configuration['embed_block_views_configuration']));
  }
  //$embed_block_views = ['media_and_press_articles:block_2', 'media_and_press:block_1'];
  foreach ($embed_block_views as $embed_block_view) {
	  $view_output = $this->render(trim($embed_block_view));
	  if ($view_output) {
		  $build[] = [
              '#theme' => 'embed_views_block',
              '#block_content' => $view_output['content'],
			  '#block_title' => $view_output['title'],
              '#cache' => ['max-age' => 0],
          ];
		  //$output .= render($view_output);
	  }
  }
	  /*
	 return [
			  '#markup' => $output,
			  '#cache' => ['max-age' => 0],

			];
		*/
	
	return $build;
  }
  
  /**
   * {@inheritdoc}
   */
  public function render($view_display_arguments) {
	$view = '';
    if (!empty($view_display_arguments)) {
		  list($view_name, $display_id) = explode(':', $view_display_arguments);
		  $view =  \Drupal::service('entity.manager')->getStorage('view')->load($view_name)->getExecutable();
		  
		  if (empty($view) || !$view->access($display_id)) {
			return [];
		  }
		  $view->setDisplay($display_id);  
		  $title = $view->getTitle();
		  $output = $view->preview($display_id, $view->args);
		  return ($output) ? ['title' => $title, 'content' => $output] : [];
		  
	}
	return [];
  }
}
