<?php

namespace Drupal\app_helper_libs\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\app_helper_libs\Form\EmailSubscriptionForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Email Subscription' block.
 *
 * @Block(
 *   id = "email_subscription",
 *   admin_label = @Translation("Subscribe to the app Newsletter"),
 *   category = @Translation("Forms")
 * )
 */
class EmailSubscriptionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * This method sets the block default configuration. This configuration
   * determines the block's behavior when a block is initially placed in a
   * region. Default values for the block configuration form should be added to
   * the configuration array. System default configurations are assembled in
   * BlockBase::__construct() e.g. cache setting and block title visibility.
   *
   * @see \Drupal\block\BlockBase::__construct()
   */
  public function defaultConfiguration() {
    /* return [
		'subscription_default_desc' => $this->t('Received daily inspiration and support to help you with Upa Yoga Practices.'),
		'subscription_page_type' => '',
		'subscription_page_type_path' => '',
		'subscription_form_type' => ''
    ]; */
  	
  	return $this->configuration;
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm(new EmailSubscriptionForm($this->configuration));
  }
  
  /**
   * {@inheritdoc}
   *
   */
  public function blockAccess(AccountInterface $account) {
  	// By default, the block is visible.
  	$subscription_domains = $this->configuration['subscription_domain'] ? array_filter($this->configuration['subscription_domain']):[];
  	$module_handler = \Drupal::service('module_handler');
  	$domainAccess = false;
  	if ($module_handler->moduleExists('domain')) {
  		$loader = \Drupal::service('domain.negotiator');
  		$current_domain = $loader->getActiveDomain();
  		$current_domain_id = $current_domain->get('id');
  		if (in_array($current_domain_id, $subscription_domains)) {
  			$domainAccess = true;
  		}
  	}
  	
  	$page_type_path = $this->configuration['subscription_page_type_path'];
  	$current_path = \Drupal::service('path.current')->getPath();
  	$path_alias = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
  	$page_type_paths = preg_split('/\r\n|[\r\n]/', $page_type_path);
  	foreach ($page_type_paths as $specific_path) {
  		if (\Drupal::service('path.matcher')->matchPath($path_alias, $specific_path)) {
  			return AccessResult::allowed();
  		}
  	}
  	return AccessResult::forbidden();
  }
  
  /**
   * {@inheritdoc}
   *
   * This method defines form elements for custom block configuration. Standard
   * block configuration fields are added by BlockBase::buildConfigurationForm()
   * (block title and title visibility) and BlockFormController::form() (block
   * visibility settings).
   *
   * @see \Drupal\block\BlockBase::buildConfigurationForm()
   * @see \Drupal\block\BlockFormController::form()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['subscription_default_desc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('This text will appear in the description placeholder of the block.'),
      '#default_value' => $this->configuration['subscription_default_desc'],
    ];
    
  	$subscription_page_type_options = [];
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('config_pages')) {
    	$newsLetterFormTypeConfig = config_pages_config('newsletter_form_type_list');
    	if($newsLetterFormTypeConfig) {
    		$paragraphSubscriptionList = $newsLetterFormTypeConfig->get('field_subscription_form_types')->referencedEntities();
    		foreach ($paragraphSubscriptionList as $paragraphEntity) {
    			$form_type_name = $paragraphEntity->get('field_form_type_name')->getString();
    			$form_type_machine_name = $paragraphEntity->get('field_form_type_machine_name')->getString();
    			$subscription_page_type_options[$form_type_machine_name] = $form_type_name;
    		}
    	}
    }
    $form['subscription_page_type'] = [
    		'#type' => 'select',
    		'#title' => $this->t('Page Type'),
    		'#options' => $subscription_page_type_options,
    		'#description' => $page_type_desc,
    		'#default_value' => $this->configuration['subscription_page_type'],
    ];
    $form['subscription_page_type_path'] = [
    		'#type' => 'textarea',
    		'#title' => $this->t('Page Type Path'),
    		'#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is /wisdom/* for every wisdom page. \<\front\>\ is the front page."),
    		'#default_value' => $this->configuration['subscription_page_type_path'],
    ];
    $form['subscription_form_type'] = [
    		'#type' => 'select',
    		'#title' => $this->t('Form Type'),
    		'#description' => $this->t('1 => Without "Never miss another update" form, 2 => With "Never miss another update" form.'),
    		'#default_value' => $this->configuration['subscription_form_type'],
    		'#options' => [
    				'type-1' => $this->t('Type-1'),
    				'type-2' => $this->t('Type-2')
    		]
    ];
    $module_handler = \Drupal::service('module_handler');
    $domain_options = [];
    if ($module_handler->moduleExists('domain')) {
    	$loader = \Drupal::service('domain.loader');
    	$domains = $loader->loadMultiple();
    	foreach($domains as $domain_id => $domain) {
    		$domain_options[$domain_id] = $domain->get('name');
    	}
    }
    $form['subscription_domain'] = [
    		'#type' => 'checkboxes',
    		'#title' => $this->t('Domain'),
    		'#default_value' => $this->configuration['subscription_domain'],
    		'#options' => $domain_options
    ];
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   *
   * This method processes the blockForm() form fields when the block
   * configuration form is submitted.
   *
   * The blockValidate() method can be used to validate the form submission.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['subscription_default_desc'] = $form_state->getValue('subscription_default_desc');
    $this->configuration['subscription_page_type'] = $form_state->getValue('subscription_page_type');
    $this->configuration['subscription_page_type_path'] = $form_state->getValue('subscription_page_type_path');
    $this->configuration['subscription_form_type'] = $form_state->getValue('subscription_form_type');
    $this->configuration['subscription_domain'] = $form_state->getValue('subscription_domain');
  }

}