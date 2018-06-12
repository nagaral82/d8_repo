<?php

/**
 * @file
 * Contains \Drupal\app_helper_libs\Plugin\views\style\MediaReference.
 */

namespace Drupal\app_helper_libs\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "media_reference_plugin",
 *   title = @Translation("Vegetables Serializer"),
 *   help = @Translation("Serializes views row data using the MediaReference component."),
 *   display_types = {"data"}
 * )
 */
class MediaReference extends Serializer {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = array();
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    $rows = [
      'farm' => [
        'title' => 'Great farm',
        'owner' => 'John Doe',
        'size' => '5 acres',
      ],
      'items' => $rows
    ];
    return $this->serializer->serialize($rows, $content_type);
  }
}