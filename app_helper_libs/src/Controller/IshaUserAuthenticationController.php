<?php
/**
 * @file
 * Contains \Drupal\hello\HelloController.
 */

namespace Drupal\app_helper_libs\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AnonymousUserSession;


/*
 * https://api.drupal.org/api/drupal/core%21includes%21bootstrap.inc/function/_drupal_exception_handler/8.2.x
 * https://drupal.stackexchange.com/questions/23290/how-do-i-get-only-one-result-using-db-query
 * https://stackoverflow.com/questions/1095831/mysql-get-the-date-n-days-ago-as-a-timestamp
 * https://api.drupal.org/api/drupal/core%21globals.api.php/global/base_url/8.2.x
 * https://drupal.stackexchange.com/questions/225189/how-do-i-redirect-to-an-external-url-without-caching-it
 *
 */

class appUserAuthenticationController extends ControllerBase {
	public function userLogout() {
	  $user = \Drupal::currentUser();
	  \Drupal::logger('user')->notice('Session closed forappUserAuthenticationController  %name.', ['%name' => $user->getAccountName()]);

	  $previousUrl = \Drupal::request()->server->get('HTTP_REFERER');
	  \Drupal::logger('user')->notice('Session closed for %name.', ['%name' => $user->getAccountName()]);
	  \Drupal::moduleHandler()->invokeAll('user_logout', [$user]);
	  // Destroy the current session, and reset $user to the anonymous user.
	  // Note: In Symfony the session is intended to be destroyed with
	  // Session::invalidate(). Regrettably this method is currently broken and may
	  // lead to the creation of spurious session records in the database.
	  // @see https://github.com/symfony/symfony/issues/12375
	  \Drupal::service('session_manager')->destroy();
	  $user->setAccount(new AnonymousUserSession());

	  $isFound = false;
	  $homeRedirectArr = [];
	  $homeRedirectArr[] = '/my-account/profile';
	  $homeRedirectArr[] = '/my-account/my-bookmark';
	  $homeRedirectCnt = count($homeRedirectArr);
	  for($i=0;$i<$homeRedirectCnt AND !$isFound;$i++) {
	  	if(strpos($previousUrl, $homeRedirectArr[$i]) !== false) {
	  		$previousUrl = '';
	  		$isFound = true;
	  	}
	  }

	  if ($previousUrl != '') {

	  	/*
	  	 * Check for "program-registration" in the URL
	  	 * Sample URL as:- https://devapp.sadhguru.org/in/en/program-registration/13253/162/IN
	  	 */

	  	$previousUrlArr = explode('/', $previousUrl);
	  	if (isset($previousUrlArr[5]) AND $previousUrlArr[5] == 'program-registration') {
	  		return $this->redirect('<front>');
	  	}

	  	return (new RedirectResponse($previousUrl, 302));
	  }
	  return $this->redirect('<front>');
	}
}