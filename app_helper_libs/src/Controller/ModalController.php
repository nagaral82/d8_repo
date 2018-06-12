<?php

namespace Drupal\app_helper_libs\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;




class ModalController extends ControllerBase {
  
  /**
   * @return AjaxResponse
   */
  public function showOptions($format = 'popup') {
      $response = new AjaxResponse();
      $options = [
        'width' => '800', // apply or override the width of the dialog
      ];      
      
    $form['block_desc'] = [
      '#markup' => t('Choose from the following options'),
    ];
    
    # the drupal checkboxes form field definition
    $form['subscribe'][] = array(
        '#title' => t('app Newsletter'),
        '#name' => 'app Newsletter',
        '#type' => 'checkbox',
        '#description' => t('Select the pizza toppings you would like.'),
        '#options' => $newsletter,
    );    
    $form['subscribe'][] = array(
        '#title' => t('Mystic Quotes'),
        '#name' => 'Mystic Quotes',
        '#type' => 'checkbox',
        '#description' => t('Select the pizza toppings you would like.'),
        '#options' => $newsletter,
    ); 
    $form['subscribe'][] = array(
        '#title' => t('Sadhguru Spot'),
        '#name' => 'Sadhguru Spot',
        '#type' => 'checkbox',
        '#description' => t('Select the pizza toppings you would like.'),
        '#options' => $newsletter,
    ); 	
    $form['notes_desc'] = [
      '#markup' => t('By subscribing you agree to app\'s Terms & Conditions and Privacy policy. 
                      You also agree to receive subsequent email and third party communications,
                       which you may opt out of at any time.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Subscribe',
    ];  
    
    $response->addCommand(new OpenModalDialogCommand('Never miss another update', render($form), $options));
    return $response;
  }
}