<?php

namespace Drupal\app_helper_libs\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;
/**
 * Provides the comment delete confirmation form.
 */
class DeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
	$nid = db_query("SELECT entity_id FROM {comment_field_data} WHERE cid = :cid", array(':cid' => $this->entity->id()))->fetchField();
	return ($nid) ? Url::fromRoute('entity.node.canonical', ['node' => $nid]) : $this->entity->get('entity_id')->entity->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Any replies to this comment will be lost. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The comment and all its replies have been deleted.');
  }

  /**
   * {@inheritdoc}
   */
  public function logDeletionMessage() {
    $this->logger('comment')->notice('Deleted comment @cid and its replies.', ['@cid' => $this->entity->id()]);
  }

}
