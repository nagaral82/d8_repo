<?php

namespace Drupal\app_helper_libs\Services;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\media_entity\Entity;
use Drupal\media_entity\Entity\Media;
use Drupal\app_quotes_migrate\appQuotesFile;
use Drupal\Core\Routing\TrustedRedirectResponse;
use \GuzzleHttp\Client;

/**
 * Helper functions for Quotes service.
 */
class QuotesService {

  public function generateQuotes() {
	/*
	 * https://api.drupal.org/api/drupal/core%21includes%21bootstrap.inc/function/_drupal_exception_handler/8.2.x
	 * https://drupal.stackexchange.com/questions/23290/how-do-i-get-only-one-result-using-db-query
	 * https://stackoverflow.com/questions/1095831/mysql-get-the-date-n-days-ago-as-a-timestamp
	 * https://api.drupal.org/api/drupal/core%21globals.api.php/global/base_url/8.2.x
	 *
	 */
  	global $base_url;
	$cid = 'Quotes_service_call_cache';
	if ($cache = \Drupal::cache()->get($cid)) {
		return(false);
	}
	$data = 'Quotes API not called for 15 minutes';
	\Drupal::cache()->set($cid, $nodeId, strtotime("+15 minutes"));

 	$quotesLangArr = ['TA', 'HI', 'TA', 'RU', 'TE', 'MA', 'KA', 'MR'];
 	$quotesLangCnt = count($quotesLangArr);

  	$sql = "SELECT nid, FROM_UNIXTIME(created, '%Y-%m-%d') AS created_date, DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(created, '%Y-%m-%d')) AS day_num
  			FROM {node_field_data} WHERE type = :type ORDER BY created DESC LIMIT 1";
  	$row = db_query($sql, array(':type' => 'app_quotes'))->fetchObject();
  	/*
  	 * Creating nodes for missing all days from last update date to current date
  	 * For all days with all languages
  	 */
  	for($i=1;$i<=$row->day_num AND $i<=3 ;$i++) {
  		$created = strtotime("+$i day", strtotime($row->created_date));
  		$schedule =  date("Y-m-d", $created);
  		$yearTitle = date('F d,Y', $created);
  		$mysqlScheduleDate =  date("Y-m-d", $created);
  		$mysqlQuery = "SELECT UNIX_TIMESTAMP('".$mysqlScheduleDate." 08:0:00') AS created";
  		$mysqlScheduelRow = db_query($mysqlQuery)->fetchObject();
  		$mysqlCreated = $mysqlScheduelRow->created;
  		$api_url = 'http://quotes.app.in/dmq/index.php/Webservice/fetchDailyQuote?date='.$schedule.'&language_code=EN';

  		$guzzle = new Client();
  		$response = $guzzle->get($api_url, ['verify' => false]);
  		if ($response->getStatusCode() >= 400) {
  			$args = array('%error' => $response->getStatusCode(), '%uri' => $uri);
  			$message = t('HTTP response: %error. URI: %uri', $args);
  			\Drupal::logger('quotesService_generateQuotes_error_response_code')->debug(print_r($message, TRUE));
  			return '';
  		}

  		$content = (string) $response->getBody();
  		$contentObj = json_decode($content);
  		if ($content == '' OR !is_object($contentObj)) {
  			\Drupal::logger('quotesService_generateQuotes_error_request_url')->debug(print_r($api_url, TRUE));
  			\Drupal::logger('quotesService_generateQuotes_error_response')->debug(print_r($content, TRUE));
  			return '';
  		}
  		\Drupal::logger('quotesService_generateQuotes_response')->debug(print_r($content, TRUE));

  		/*
  		 * Generate the node with default as English ('en') language
  		 */
  		if ($content != '') {
  			$target_language = getDrupalLangCode('en');
  			$node = Node::create(array(
  					'title' => $yearTitle,
  					'type' => 'app_quotes',
  					'langcode' => $target_language,
  					'uid' => '1',
  			));
  			$node  = $this->convertDrupalNode($content, $node, $target_language, $mysqlCreated, $yearTitle);

  			/*
  			 * Translate for each language as associate information for English Node Content
  			 */

  			for($j=0;$j<$quotesLangCnt;$j++) {
  				$api_url = 'http://quotes.app.in/dmq/index.php/Webservice/fetchDailyQuote?date='.$schedule.'&language_code='.$quotesLangArr[$j];
  				$langContent = '';

  				$guzzle = new Client();
  				$response = $guzzle->get($api_url, ['verify' => false]);
  				if ($response->getStatusCode() >= 400) {
  					$args = array('%error' => $response->getStatusCode(), '%uri' => $uri);
  					$message = t('HTTP response: %error. URI: %uri', $args);
  					\Drupal::logger('quotesService_generateQuotes_error_response_code')->debug(print_r($message, TRUE));
  				} else {
  					$langContent = (string) $response->getBody();
	  				$contentObj = json_decode($langContent);
	  				if ($langContent == '' OR !is_object($contentObj)) {
	  					\Drupal::logger('quotesService_generateQuotes_error_request_url')->debug(print_r($api_url, TRUE));
	  					\Drupal::logger('quotesService_generateQuotes_error_response')->debug(print_r($langContent, TRUE));
	  				} else {
  						\Drupal::logger('quotesService_generateQuotes_response')->debug(print_r($langContent, TRUE));
	  				}
  				}

  				$target_language = getDrupalLangCode($quotesLangArr[$j]);
  				if($langContent != '' AND $target_language != '') {
  					$jsonData = json_decode($langContent);
  					$lang_text = (isset($jsonData->response->data[0]->lang_text))?$jsonData->response->data[0]->lang_text:'';
  					if ($lang_text != '') {
	  					if (!$node->hasTranslation($target_language)) {
	  							$dstNode = $node->addTranslation($target_language);
		  						$dstNode->title = $yearTitle;
	  							$this->convertDrupalNode($langContent, $dstNode, $target_language, $mysqlCreated, $yearTitle);
	  					}
  					}
  				}
  			}//For each language
  		} //Content
  	}

  	$data = 'Quotes API not called for 15 minutes';
  	\Drupal::cache()->set($cid, $nodeId, strtotime("+15 minutes"));
  }


    public function convertDrupalNode($content, $node, $language, $created, $yearTitle) {
  	foreach (\Drupal::service('domain.loader')->loadMultipleSorted() as $domain) {
  		$options = array('absolute' => TRUE, 'https' => $domain->isHttps());
  		$countries[] = $domain->id();
  	}

  	$node->set('field_domain_access', $countries);
  	$node->set('field_domain_all_affiliates', '1');

  	$jsonData = json_decode($content);
  	$eng_text = (isset($jsonData->response->data[0]->eng_text))?$jsonData->response->data[0]->eng_text:'';
  	$lang_text = (isset($jsonData->response->data[0]->lang_text))?$jsonData->response->data[0]->lang_text:'';
  	$id = (isset($jsonData->response->data[0]->id))?$jsonData->response->data[0]->id:'0';
  	$show_signature = (isset($jsonData->response->data[0]->show_signature))?$jsonData->response->data[0]->show_signature:'0';
  	$special_day = (isset($jsonData->response->data[0]->announcement))?$jsonData->response->data[0]->announcement:'0';
  	$image_name  = (isset($jsonData->response->data[0]->image_name))?$jsonData->response->data[0]->image_name:'0';

  	/*
  	 * For US it will be available in lang_text
  	 * Need to be assign in the field_country_quote column
  	 */
	if ($language == 'en') {
  		$node->field_country_quote->value = $lang_text;
  		$node->field_country_quote->format =  'full_html';
	} else {
		$node->field_general_quote->value = $lang_text;
		$node->field_general_quote->format =  'full_html';
	}

  	$node->field_english_quote->value = $eng_text;
  	$node->field_english_quote->format =  'full_html';

  	$node->set('field_quotes_id', $id);
  	$node->set('field_quotes_show_signature', $show_signature);

  	$node->field_quotes_special_day->value = $special_day;
  	$node->field_quotes_special_day->format =  'full_html';

  	$node->setChangedTime($created);
  	$node->setCreatedTime($created);
  	/*
  	 * File Upload
  	 */
  	$quotesFileObj = new appQuotesFile();
  	try {
  		if ($image_name != '') {
  			$image_alt = $yearTitle;
  			$source = 'quotes';
  			$fileData = $quotesFileObj->process($image_name, $image_alt, $source, $language, $id);
  			if (is_array($fileData)) {
  				$node->set('field_quotes_image', $fileData);
  			}
  		}
  	} 	catch (\Throwable $error) {
  	}
  	catch (\Exception $exception2) {
  	}

  	$tag = [4926]; //4661 //4926;
  	$node->set('field_app_tags', $tag);
  	$node->save();
  	return $node;
  }
}
