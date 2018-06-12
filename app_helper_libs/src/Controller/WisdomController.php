<?php
/**
 * @file
 * Contains \Drupal\app_helper_libs\Controller\WisdomController.
 */

namespace Drupal\app_helper_libs\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;




class WisdomController extends ControllerBase {
  
  /**
   * @return AjaxResponse
   */
  public function showContents($paramkey, $paramdata) {
      //drupal_set_message(print_r($argdata,TRUE));
      $response['#markup'] = render(views_embed_view('wisdom_grid_view','block_8'));
      
    return $response;
  }
  


}