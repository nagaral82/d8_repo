<?php

namespace Drupal\app_helper_libs\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change path '/user/login' to '/login'.
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\app_helper_libs\Controller\EntityAutocompleteController::handleAutocomplete');
    }
	// /user/logout to custom logout
    if ($route = $collection->get('user.logout')) {
      $route->setDefault('_controller', '\Drupal\app_helper_libs\Controller\appUserAuthenticationController::userLogout');
    }
  }

}