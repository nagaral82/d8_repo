<?php

namespace Drupal\app_helper_libs\Services;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Helper functions for country service.
 */
class CountryService {

	/**
	* Returns the country list with India and US at the top.
	*
	* @return array $countryList 
	*/
	public function getCountryList() {
		$countryList = \Drupal\Core\Locale\CountryManager::getStandardList();
		$countryList['UK'] = $countryList['GB'];
		$top_list = [];
		$top_list['IN'] = $countryList['IN'];
		$top_list['US'] = $countryList['US'];
		unset($countryList['GB']);
		unset($countryList['IN']);
		unset($countryList['US']);
		/*
		 * Unset few more countries to make match with joomla DB
		 */
		unset($countryList['AC']);
		unset($countryList['AX']);
		unset($countryList['BL']);
		unset($countryList['BQ']);
		unset($countryList['CW']);
		unset($countryList['DG']);
		unset($countryList['EA']);
		unset($countryList['GG']);
		unset($countryList['IC']);
		unset($countryList['IM']);
		unset($countryList['JE']);
		unset($countryList['ME']);
		unset($countryList['MF']);
		unset($countryList['QO']);
		unset($countryList['RS']);
		unset($countryList['SS']);
		unset($countryList['SX']);
		unset($countryList['TA']);
		unset($countryList['XK']);
		natcasesort($countryList);
		$newCountryList = array_merge($top_list, $countryList);

		return $newCountryList;
	}

}
