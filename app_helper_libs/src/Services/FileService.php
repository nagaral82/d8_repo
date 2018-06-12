<?php

namespace Drupal\app_helper_libs\Services;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\file\Entity\File as CoreFile;
/**
 * Helper functions for file service.
 */
class FileService {
	
	/**
 * Saves a file to the specified destination and creates a database entry.
 *
 * @param string $data
 *   A string containing the contents of the file.
 * @param string|null $destination
 *   (optional) A string containing the destination URI. This must be a stream
 *   wrapper URI. If no value or NULL is provided, a randomized name will be
 *   generated and the file will be saved using Drupal's default files scheme,
 *   usually "public://".
 * @param int $replace
 *   (optional) The replace behavior when the destination file already exists.
 *   Possible values include:
 *   - FILE_EXISTS_REPLACE: Replace the existing file. If a managed file with
 *     the destination name exists, then its database entry will be updated. If
 *     no database entry is found, then a new one will be created.
 *   - FILE_EXISTS_RENAME: (default) Append _{incrementing number} until the
 *     filename is unique.
 *   - FILE_EXISTS_ERROR: Do nothing and return FALSE.
 *
 * @return \Drupal\file\FileInterface|false
 *   A file entity, or FALSE on error.
 *
 * @see file_unmanaged_save_data()
 */
public function fileSaveData($data, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  $user = \Drupal::currentUser();
  if (function_exists('get_content_migration_default_value')) {
	$language = get_content_migration_default_value();
  }
  else {
	$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }
  if (empty($destination)) {
    $destination = file_default_scheme() . '://';
  }
  if (!file_valid_uri($destination)) {
    \Drupal::logger('file')->debug('The data could not be saved because the destination %destination is invalid. This may be caused by improper use of file_save_data() or a missing stream wrapper.', ['%destination' => $destination]);
    drupal_set_message(t('The data could not be saved because the destination is invalid. More information is available in the system log.'), 'error');
    return FALSE;
  }

  if ($uri = file_unmanaged_save_data($data, $destination, $replace)) {
    // Create a file entity.
    $file = CoreFile::create([
      'uri' => $uri,
      'uid' => $user->id(),
	  'langcode' => $language,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    // If we are replacing an existing file re-use its database record.
    // @todo Do not create a new entity in order to update it. See
    //   https://www.drupal.org/node/2241865.
    if ($replace == FILE_EXISTS_REPLACE) {
      $existing_files = entity_load_multiple_by_properties('file', ['uri' => $uri]);
      if (count($existing_files)) {
        $existing = reset($existing_files);
        $file->fid = $existing->id();
        $file->setOriginalId($existing->id());
        $file->setFilename($existing->getFilename());
      }
    }
    // If we are renaming around an existing file (rather than a directory),
    // use its basename for the filename.
    elseif ($replace == FILE_EXISTS_RENAME && is_file($destination)) {
      $file->setFilename(drupal_basename($destination));
    }

    $file->save();
    return $file;
  }
  return FALSE;
	}
}
