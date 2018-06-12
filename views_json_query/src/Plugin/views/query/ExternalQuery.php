<?php

/**
 * @file
 * Definition of Drupal\views_json_query\Plugin\views\query\ExternalQuery
 */

namespace Drupal\views_json_query\Plugin\views\query;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * External API views query plugin which wraps calls to the external API in order to
 * expose the results to views.
 *
 * @ViewsQuery(
 *   id = "external_json_api",
 *   title = @Translation("External Query"),
 *   help = @Translation("Query against the exposed API.")
 * )
 */
class ExternalQuery extends QueryPluginBase {
	
	public function ensureTable($table, $relationship = NULL) {
		return '';
	}
	public function addField($table, $field, $alias = '', $params = array()) {
		return $field;
	}
	/**
	 * {@inheritdoc}
	 */
	public function execute(ViewExecutable $view) {
		try {
      
		  if ($contents = $this->fetch_file($this->options['json_file'])) {
			$ret = $this->parse($view, $contents);
		  }
		}
		catch (\Exception $e) {
		  drupal_set_message(
			t('Views Json Query') . ': ' . $e->getMessage(), 'error'
		  );
		  return;
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function buildOptionsForm(&$form, FormStateInterface $form_state) {
	  parent::buildOptionsForm($form, $form_state);
	  $form['json_file'] = array(
      '#type' => 'textfield',
      '#title' => t('Json File'),
      '#default_value' => $this->options['json_file'],
      '#description' => t("The URL or path to the Json file."),
      '#maxlength' => 1024,
    );
    $form['row_apath'] = array(
      '#type' => 'textfield',
      '#title' => t('Row Apath'),
      '#default_value' => ($this->options['row_apath']) ? $this->options['row_apath']: 0,
      '#description' => t("Apath to records.<br />Apath is just a simple array item find method. Ex:<br /><pre>array('data' => \n\tarray('records' => \n\t\tarray(\n\t\t\tarray('name' => 'yarco', 'sex' => 'male'),\n\t\t\tarray('name' => 'someone', 'sex' => 'male')\n\t\t)\n\t)\n)</pre><br />You want 'records', so Apath could be set to 'data/records'. <br />Notice: prefix '/' or postfix '/' will be trimed, so never mind you add it or not."),
      '#required' => TRUE,
    );
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function defineOptions() {
	  $options['json_file'] = array('default' => '');
	  $options['row_apath'] = array('default' => '');
	  return $options;
	}
	
	/**
   * Parse.
   */
  function parse(&$view, $contents) {
    $ret = json_decode($contents, FALSE);
    if (!$ret) {
      return FALSE;
    }
	
    // Get rows.
    $ret = ($this->options['row_apath']) ? $this->apath($this->options['row_apath'], $ret) : $ret;
	
	
	if ($view->pager->useCountQuery() || !empty($view->get_total_rows)) {
        // Hackish execute_count_query implementation.
        $view->pager->total_items = count($ret);
        if (!empty($this->pager->options['offset'])) {
          $view->pager->total_items -= $view->pager->options['offset'];
        }

        $view->pager->updatePageInfo();
      }

      // Deal with offset & limit.
      $offset = !empty($this->offset) ? intval($this->offset) : 0;
      $limit = !empty($this->limit) ? intval($this->limit) : 0;
	  if (count($ret) > $limit) {
          $ret = $limit ? array_slice($ret, $offset, $limit) : array_slice($ret, $offset);
      }
	  
	  
    try {
      $result = array();
	   $index = 0;
      foreach ($ret as $row) {
        $result_row = (array)$this->parse_row(NULL, $row, $row);
        $result_row['index'] = $index++;
        $result[] = new ResultRow($result_row);
      }
      $view->result = $result;
      $view->total_rows = count($result);
      return TRUE;
    }
    catch (\Exception $e) {
      $view->result = array();
      if (!empty($view->live_preview)) {
        drupal_set_message(time());
        drupal_set_message($e->getMessage(), 'error');
      }
      else {
        debug($e->getMessage(), 'Views Json Backend');
      }
    }
  }
  
  /**
   * Fetch data in array according to apath.
   *
   * @param string $apath
   *   Something like '1/name/0'
   *
   * @param array $array
   *
   * @return array
   */
  function apath($apath, $array) {
    $r = & $array;
    $paths = explode('/', trim($apath, '//'));
    foreach ($paths as $path) {
      if (is_array($r) && isset($r[$path])) {
        $r = & $r[$path];
      }
      elseif (is_object($r)) {
        $r = & $r->$path;
      }
      else {
        break;
      }
    }

    return $r;
  }
	/**
   * Parse row.
   *
   * A recursive function to flatten the json object.
   * Example:
   * {person:{name:{first_name:"John", last_name:"Doe"}}}
   * becomes:
   * $row->person/name/first_name = "John",
   * $row->person/name/last_name = "Doe"
   */   
  function parse_row($parent_key, $parent_row, &$row) {
    $props = get_object_vars($parent_row);
	
    foreach ($props as $key => $value) {
		
      if (is_object($value)) {
        unset($row->$key);
        $this->parse_row(
          is_null($parent_key) ? $key : $parent_key . '/' . $key,
          $value,
          $row
        );
      }
      else {
        if ($parent_key) {
          $new_key = $key;
          $row->$new_key = $value;
        }
        else {
          $row->$key = $value;
        }
      }
    }
	
    return $row;
  }
 

  /**
   * Fetch file.
   */
  function fetch_file($uri) {
    $parsed = parse_url($uri);
    // Check for local file.
    if (empty($parsed['host'])) {
      if (!file_exists($uri)) {
        throw new Exception(t('Local file not found.'));
      }
      return file_get_contents($uri);
    }

    $destination = 'public://views_json_query';
    if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY |
      FILE_MODIFY_PERMISSIONS)) {
      throw new Exception(
        t('Files directory either cannot be created or is not writable.')
      );
    }

    $headers = array();
    $cache_file = 'views_json_query_' . md5($uri);
    if ($cache = \Drupal::cache()->get($cache_file)) {
      $last_headers = $cache->data;

      if (!empty($last_headers['etag'])) {
        $headers['If-None-Match'] = $last_headers['etag'];
      }
      if (!empty($last_headers['last-modified'])) {
        $headers['If-Modified-Since'] = $last_headers['last-modified'];
      }
    }
	try {
		$result = \Drupal::httpClient()->get($uri, ['verify' => false, 'headers' => $headers]);
		$data = (string) $result->getBody();
	}
    catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
    $cache_file_uri = "$destination/$cache_file";
    if ($result->getStatusCode() == 304) {
      if (file_exists($cache_file_uri)) {
        return file_get_contents($cache_file_uri);
      }
      // We have the headers but no cache file. :(
      // Run it back.
      \Drupal::cache()->delete($cache_file);
      return $this->fetch_file($uri);
    }

    // As learned from Feeds caching mechanism, save to file.
    file_save_data($data, $cache_file_uri, FILE_EXISTS_REPLACE);
    \Drupal::cache()->set($cache_file, $result->getHeaders());
    return $data;
  }
  
  /**
   * Add field.
   */
  function add_field($table, $field, $alias = '', $params = array()) {
    $alias = $field;

    // Add field info array.
    if (empty($this->fields[$field])) {
      $this->fields[$field] = array(
        'field' => $field,
        'table' => $table,
        'alias' => $alias,
      ) + $params;
    }

    return $field;
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    // Mostly modeled off of \Drupal\views\Plugin\views\query\Sql::build()

    // Store the view in the object to be able to use it later.
    $this->view = $view;

    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = $this->query(TRUE);
  }

   /**
   * {@inheritdoc}
   */
  public function query($get_count = FALSE) {
    // Fill up the $query array with properties that we will use in forming the
    // API request.
    $query = [];

    // Iterate over $this->where to gather up the filtering conditions to pass
    // along to the API. Note that views allows grouping of conditions, as well
    // as group operators. This does not apply to us, as the Fitbit API has no
    // such concept, nor do we support this concept for filtering connected
    // Fitbit Drupal users.
    if (isset($this->where)) {
      foreach ($this->where as $where_group => $where) {
        foreach ($where['conditions'] as $condition) {
          // Remove dot from begining of the string.
          $field_name = ltrim($condition['field'], '.');
          $query[$field_name] = $condition['value'];
        }
      }
    }

    return $query;
  }
  
  /**
   * Adds a simple condition to the query. Collect data on the configured filter
   * criteria so that we can appropriately apply it in the query() and execute()
   * methods.
   *
   * @param $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $field
   *   The name of the field to check.
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For more
   *   complex options, it is an array. The meaning of each element in the array is
   *   dependent on the $operator.
   * @param $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more
   *   complex options such as IN, LIKE, LIKE BINARY, or BETWEEN. Defaults to =.
   *   If $field is a string you have to use 'formula' here.
   *
   * @see \Drupal\Core\Database\Query\ConditionInterface::condition()
   * @see \Drupal\Core\Database\Query\Condition
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = [
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    ];
  }
  
  
  
}