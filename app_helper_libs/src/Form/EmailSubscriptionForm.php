<?php

namespace Drupal\app_helper_libs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \GuzzleHttp\Client;
use Drupal\app_helper_libs\SmartIp;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;


/**
 * Builds the search form for the search block.
 */
class EmailSubscriptionForm extends FormBase {

  protected $defaultConfig = [];
  
  protected $subscriptionListOptions = [];
  
  protected $subscriptionListDescriptions = [];
  
  /**
   * {@inheritdoc}
   */
  public function __construct($defaultConfig) {
    $this->defaultConfig = $defaultConfig;
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_subscription';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  	// Ensure that form is not cached for anonymous users 
    $form['#cache'] = ['max-age' => 0];
    
    if (!isset($this->defaultConfig['subscription_page_type']) || !isset($this->defaultConfig['subscription_form_type'])) {
    	$form['#access'] = FALSE;
    	drupal_set_message(t('Subscription Form Type have not been configured for Newsletter Form.'), 'error');
    	return $form;
    }
    
    $page_type = $this->defaultConfig['subscription_page_type'];
    $form_type = $this->defaultConfig['subscription_form_type'];
    $default_subscription_list_options = [];
    $api_endpoint = '';
    $default_english_newsletter = '';
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('config_pages')) {
    	$newsLetterConfig = config_pages_config('newsletter_settings');
    	if($newsLetterConfig) {
    		$api_endpoint = $newsLetterConfig->get('field_api_endpoint')->getString();
    		if($form_type == 'type-2') {
	    		$paragraphSubscriptionList = $newsLetterConfig->get('field_subscription_list')->referencedEntities();
	    		foreach ($paragraphSubscriptionList as $paragraphEntity) {
	    			$subscription_name = $paragraphEntity->get('field_subscription_name')->getString();
	    			$subscription_machine_name = $paragraphEntity->get('field_subscription_machine_name')->getString();
	    			$subscription_desc = $paragraphEntity->get('field_subscription_description')->getString();
	    			$subscription_language = $paragraphEntity->get('field_language')->getString();
	    			$this->subscriptionListOptions[$subscription_machine_name] = $subscription_name;
	    			$this->subscriptionListDescriptions[$subscription_machine_name] = $subscription_desc;
	    			
	    			$site_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
	    			if ($subscription_language == 'en') {
	    				//by default english newsletter will be selected
	    				$default_english_newsletter = $subscription_machine_name;
	    				$default_subscription_list_options[] = $subscription_machine_name;
	    			}
	    			else if($site_language == $subscription_language) {
	    				//removed default english newsletter if other language matched
		    			if (($key = array_search($default_english_newsletter, $default_subscription_list_options)) !== false) {
							unset($default_subscription_list_options[$key]);
						}
	    				$default_subscription_list_options[] = $subscription_machine_name;
	    			}
	    		}
	    		$default_subscription_list_options[] = 'SadhguruSpot';
    		}
    	}
    }
    
    if (empty($api_endpoint)) {
    	$form['container']['#access'] = FALSE;
    	drupal_set_message(t('API endpoint url is not configured for Newsletter Form.'), 'error');
    	return $form;
    }
    if ($form_type == 'type-2' && empty($this->subscriptionListOptions)) {
    	$form['container']['#access'] = FALSE;
    	drupal_set_message(t('No Subscription List have been configured for Newsletter Form.'), 'error');
    	return $form;
    }
    
  	$form_type1_wrapper_prefix = '<div class="subscription-form-type-1">';
  	$form_type1_wrapper_prefix .= '<div class="change-col col-lg-8 col-md-8 col-sm-12 col-xs-12 center-always">';
  	$form_type1_wrapper_suffix = '</div></div>';
  	$form['container'] = [
  			'#type' => 'container',
  			'#attributes' => [
  					'class' => ['row','app-form-style','no-padding']
  			],
  	];
  	
  	if($this->defaultConfig['subscription_default_desc']) {
  		$desc_class = 'app-ym-in-text';
  		if ($form_type == 'type-1') {
  			$desc_class = 'home-quote-subscribe';
  		}
  		$desc_markup = '<div class="'.$desc_class.'">'.$this->t($this->defaultConfig['subscription_default_desc']).'</div>';
  	}
  	$form['container']['form_type1_header'] = [
  			'#markup' => $desc_markup,
  			'#prefix' => $form_type1_wrapper_prefix
  	];
    $form['container']['name'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#prefix' => '<div class="col-lg-5 col-md-12 col-sm-12 col-xs-12 padding-left0">',
      '#suffix' => '</div>',
      '#default_value' => '',
	  '#required' => TRUE,
      '#attributes' => [
      		//'pattern'=> '([a-zA-Z�-�- ]){2,50}',
      		'placeholder'=> $this->t('Full Name'),
      		'title' => $this->t('Enter your name which cannot exceed 50 characters'),
      		'class' => ['subscription-name']
      ],
    ];
    $form['container']['email'] = [
      '#type' => 'email',
      '#size' => 15,
      '#prefix' => '<div class="col-lg-5 col-md-12 col-sm-12 col-xs-12 padding-left0">',
      '#suffix' => '</div>',
      '#default_value' => '',
	  '#required' => TRUE,
      '#attributes' => [
      		//'pattern' => '[A-Za-z]+[A-Za-z0-9._]+@[A-Za-z]+\.[A-Za-z.]{2,5}$',
      		'placeholder'=> $this->t('Email Address'),
      		'title' => $this->t('Enter the valid email address'),
      		'class' => ['subscription-email']
      ],
    ];
	
    $form['container']['api_endpoint'] = [
    		'#type' => 'hidden',
    		'#value' => $api_endpoint
    ];
    $form['container']['form_type'] = [
    		'#type' => 'hidden',
    		'#value' => $form_type,
    		'#attributes' => [
    				'id' => 'subscription-api-form-type'
    		]
    ];
    
    $form['container']['signup'] = [
    		'#type' => 'submit',
    		'#value' => $this->t('Subscribe'),
    		'#prefix' => '<div class="col-lg-2 col-md-4 col-sm-12 col-xs-12 no-padding">',
    		'#suffix' => '</div>' . $form_type1_wrapper_suffix,
    		'#attributes' => [
    				'class' => ['btn', 'btn-app-1'],
    				'id' => 'email-subscription-api-form1-submit'
    		]
    ];
    if($form_type == 'type-2') {
    	/* $form['container']['signup'] = [
    			'#markup' => '<a class="btn btn-app-1 button" id ="email-subscription-api-form1-submit">'.$this->t('Sign Up').'</a>',
    			'#prefix' => '<div class="col-lg-2 col-md-4 col-sm-12 col-xs-12 no-padding">',
    			'#suffix' => '</div>' . $form_type1_wrapper_suffix,
    	]; */
    	$form['container']['signup']['#value'] = $this->t('Sign Up');
    			
    	//form-type-2
    	$form_type2_wrapper_prefix = '<div class="subscribe-details-block after-button-click padding-top-1">';
    	$form_type2_wrapper_prefix .= '<div class="col-lg-10 col-md-10 col-sm-12 col-xs-12 no-padding center-always">';
    	$form_type2_wrapper_suffix = '</div></div>';
    	$form_type2_title_caption = '<div class="app-subscribe-block-title">'.$this->t('Never miss another update').'</div>';
        $form_type2_title_caption .= '<div class="app-article-caption">'.$this->t('Choose from the following options').'</div>';
        $row_prefix = '<div class="row">';
        $row_suffix = '</div>';
        $iagree_prefix = '<div class="row padding-top-n-btm">';
        $iagree_prefix .= '<div class="col-lg-10 col-md-10 col-sm-12 col-xs-12 center-always">';
        $iagree_suffix = '</div></div>';
        $iagree_content = '<div class="additional-text">By subscribing you agree to app\'s '
        		.'<span class="app-orange-text "><a href="/terms-conditions" target="_blank">Terms &amp; Conditions</a></span> and '
        		.'<span class="app-orange-text "><a href="/privacy-policy" target="_blank">Privacy policy</a>.</span> '
        		.'You also agree to receive subsequent email and third party communications, '
        		.'which you may opt out of at any time.</div>';
        $form['container']['form_type2_header'] = [
        		'#markup' => $form_type2_title_caption,
        		'#prefix' => $form_type2_wrapper_prefix
        ];
    	$form['container']['subscription_list'] = [
    			'#type' => 'checkboxes',
    			'#options' => $this->subscriptionListOptions,
    			'#default_value' => $default_subscription_list_options,
    			'#prefix' => $row_prefix,
    			'#suffix' => $row_suffix,
    			'#required' => TRUE,
    			'#attributes' => [
    					'class' => ['subscription-list-checkbox']
    			],
    			'#process' => [
    					[$this ,'processCheckboxes'],
    			]
    	];
    	$form['container']['iagree_content'] = [
    			'#markup' => $iagree_content,
    			'#prefix' => $iagree_prefix,
    	];
    	$form['container']['submit'] = [
    			'#type' => 'submit',
    			'#value' => $this->t('Subscribe'),
    			'#prefix' => '<div class="padding-top-1 tcenter">',
    			'#suffix' => '<a class="cancel-link" id="email-subscribe-cancel-button">Cancel</a></div>' . $iagree_suffix . $form_type2_wrapper_suffix,
    			'#attributes' => [
    					'class' => ['btn','btn-app-1','btn-app-custom', 'email-subscribe-button']
    			]
    	];
    }
    //$form['container']['#theme'] = 'email_subscrption_form';
	$form['container']['#attached']['library'][] = 'app_helper_libs/newsletter-form';
    
    return $form;
  }
  
  /**
   * Processes a checkboxes form element.
   */
  public function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
  	$form_type2_field_prefix = '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 checkbox-container">';
  	//$form_type2_field_prefix .= '<div class="checkbox">';
  	$form_type2_field_suffix = '</div>';
  	
  	$value = is_array($element['#value']) ? $element['#value'] : [
  
  	];
  	$element['#tree'] = TRUE;
  	if (count($element['#options']) > 0) {
  		if (!isset($element['#default_value']) || $element['#default_value'] == 0) {
  			$element['#default_value'] = [
  
  			];
  		}
  		$weight = 0;
  		foreach ($element['#options'] as $key => $choice) {
  
  			// Integer 0 is not a valid #return_value, so use '0' instead.
  			// @see \Drupal\Core\Render\Element\Checkbox::valueCallback().
  			// @todo For Drupal 8, cast all integer keys to strings for consistency
  			//   with \Drupal\Core\Render\Element\Radios::processRadios().
  			if ($key === 0) {
  				$key = '0';
  			}
  
  			// Maintain order of options as defined in #options, in case the element
  			// defines custom option sub-elements, but does not define all option
  			// sub-elements.
  			$weight += 0.001;
  			$element += [
  					$key => [
  
  					],
  			];
  			
  			$option_desc = '';
  			if(!empty($this->subscriptionListDescriptions[$key])) {
  				$option_desc = '<span class="subs-point-body text-align-left-imp">'
  					.$this->subscriptionListDescriptions[$key]
  					.'</span>';
  			}
  			$element[$key] += [
  					'#type' => 'checkbox',
  					'#title' => '<span class="contact-us-head">'.$choice.'</span>',
  					'#return_value' => $key,
  					'#default_value' => isset($value[$key]) ? $key : NULL,
  					'#attributes' => $element['#attributes'],
  					'#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
  					// Errors should only be shown on the parent checkboxes element.
  					'#error_no_message' => TRUE,
  					'#weight' => $weight,
  					'#prefix' => $form_type2_field_prefix,
  					'#description' => $option_desc,
  					'#suffix' => $form_type2_field_suffix,
  			];
  		}
  	}
  	return $element;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  	$subscription_list = $form_state->getValue('subscription_list');
  	$api_url = $form_state->getValue('api_endpoint');
  	$uid = \Drupal::currentUser()->id();
  	$name = $form_state->getValue('name');
  	$email = $form_state->getValue('email');
  	$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  	$current_uri = \Drupal::request()->getUri();
  	$currentUrl = Url::fromUri($current_uri, ['absolute' => TRUE]);
  	$current_url_string = $currentUrl->toString(TRUE)->getGeneratedUrl();
  	$country_code = "IN";
  	$type = isset($this->defaultConfig['subscription_page_type']) ? $this->defaultConfig['subscription_page_type']:'';
  	$city = '';
  	$region = '';
  	$user_ip = '';
  	if($_SERVER['HTTP_HOST'] != 'localhost') {
  		$location = SmartIp::query();
  		$country_code = strtoupper($location['countryCode']);
  		$city = $location['city'];
  		$region = $location['region'];
  		$user_ip = SmartIp::getUserIP();
  	}
  	
  	$post_data = [
  			"UID" => $uid,
  			"name" => $name,
  			"newsletters" => $subscription_list ? array_filter($subscription_list):['DMQ'=>'DMQ'],
  			"email" => $email,
  			"language" => strtoupper($language),
  			"region" => $region,
  			"country" => $country_code,
  			"city" => $city,
  			"source" => $current_url_string,
  			"type" => $type,
  			"userIP" => $user_ip
  	];
  	
  	$guzzle = new Client();
  	\Drupal::logger('Newsletter_Subscription_Request')->debug(print_r($post_data, TRUE).print_r(['apiUrl'=>$api_url], TRUE));
  	$response = $guzzle->post(trim($api_url), ['form_params' => $post_data]);
  	if ($response->getStatusCode() >= 400) {
  	
  		$args = array('%error' => $response->getStatusCode(), '%uri' => $uri);
  		$message = t('HTTP response: %error. URI: %uri', $args);
  		\Drupal::logger('Newsletter_Subscription_Response')->debug(print_r($message, TRUE));
  		throw new \Exception($message);
  	}
  	$response_data = (string) $response->getBody();
  	//$response_data = '{"status":"Success","redirectURL":"http:\/\/dev.app.sadhguru.org\/homepage-dmq-success\/"}';
  	\Drupal::logger('Newsletter_Subscription_Response')->debug(print_r($response_data, TRUE));
  	
  	if($response_data) {
	  	$responseJsonObj = json_decode($response_data);
	  	if($responseJsonObj->redirectURL) {
	  		$response = new TrustedRedirectResponse($responseJsonObj->redirectURL);
	  		$form_state->setResponse($response);
	  	}
	  	else {
	  		foreach ($responseJsonObj as $key => $value){
	  			$errorMsg = '<strong>'.$key.': </strong>';
		  		$errorMsg .= $value;
		  		$message = \Drupal\Core\Render\Markup::create($errorMsg);
		  		drupal_set_message($message, 'error');
	  		}
	  		$form_state->setRedirectUrl($currentUrl);
	  	}
  	}
  	else {
  		drupal_set_message('Error on server, please try again.', 'error');
  		$form_state->setRedirectUrl($currentUrl);
  	}
  }

}