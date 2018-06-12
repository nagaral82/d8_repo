<?php

namespace Drupal\app_helper_libs\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'file_app_url_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "file_app_url_plain",
 *   label = @Translation("app URL to file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class appUrlPlainFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $elements[$delta] = [
        '#markup' => file_create_url($file->getFileUri()),
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

}
