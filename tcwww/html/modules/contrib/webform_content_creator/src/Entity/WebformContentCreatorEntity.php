<?php

namespace Drupal\webform_content_creator\Entity;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\webform_content_creator\WebformContentCreatorInterface;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Component\Utility\Html;

/**
 * Defines the Webform Content creator entity.
 *
 * @ConfigEntityType(
 *   id = "webform_content_creator",
 *   label = @Translation("Webform Content creator"),
 *   handlers = {
 *     "list_builder" = "Drupal\webform_content_creator\Controller\WebformContentCreatorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_content_creator\Form\WebformContentCreatorForm",
 *       "edit" = "Drupal\webform_content_creator\Form\WebformContentCreatorForm",
 *       "delete" = "Drupal\webform_content_creator\Form\WebformContentCreatorDeleteForm",
 *       "manage_fields" = "Drupal\webform_content_creator\Form\WebformContentCreatorManageFieldsForm",
 *     }
 *   },
 *   config_prefix = "webform_content_creator",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "title" = "title",
 *     "webform" = "webform",
 *     "content_type" = "content_type",
 *   },
 *   links = {
 *     "manage-fields-form" = "/admin/config/webform_content_creator/manage/{webform_content_creator}/fields",
 *     "edit-form" = "/admin/config/webform_content_creator/{webform_content_creator}",
 *     "delete-form" = "/admin/config/webform_content_creator/{webform_content_creator}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "webform",
 *     "content_type",
 *     "field_title",
 *     "use_encrypt",
 *     "encryption_profile",
 *     "sync_content",
 *     "sync_content_delete",
 *     "sync_content_node_field",
 *     "create_nodes_manually",
 *     "elements",
 *   }
 * )
 */
class WebformContentCreatorEntity extends ConfigEntityBase implements WebformContentCreatorInterface {

  use StringTranslationTrait, MessengerTrait;

  /**
   * Webform content creator entity id.
   *
   * @var string
   */
  protected $id;

  /**
   * Webform content creator entity title.
   *
   * @var string
   */
  protected $title;

  /**
   * Node title.
   *
   * @var string
   */
  protected $field_title;

  /**
   * Webform machine name.
   *
   * @var string
   */
  protected $webform;

  /**
   * Content type machine name.
   *
   * @var string
   */
  protected $content_type;

  /**
   * Mapping between webform submission values and node field values.
   *
   * @var array
   */
  protected $elements;

  /**
   * Use encryption.
   *
   * @var bool
   */
  protected $use_encrypt;

  /**
   * Encryption profile.
   *
   * @var string
   */
  protected $encryption_profile;

  /**
   * Returns the entity title.
   *
   * @return string
   *   The entity title.
   */
  public function getTitle() {
    return $this->get('title');
  }

  /**
   * Sets the entity title.
   *
   * @param string $title
   *   Node title.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * Returns the entity content type id.
   *
   * @return string
   *   The entity content type.
   */
  public function getContentType() {
    return $this->get('content_type');
  }

  /**
   * Sets the content type entity.
   *
   * @param string $contentType
   *   Content type entity.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setContentType($contentType) {
    $this->set('content_type', $contentType);
    return $this;
  }

  /**
   * Returns the entity webform id.
   *
   * @return string
   *   The entity webform.
   */
  public function getWebform() {
    return $this->get('webform');
  }

  /**
   * Sets the entity webform id.
   *
   * @param string $webform
   *   Webform id.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setWebform($webform) {
    $this->set('webform', $webform);
    return $this;
  }

  /**
   * Returns the entity attributes as an associative array.
   *
   * @return array
   *   The entity attributes mapping.
   */
  public function getAttributes() {
    return $this->get(WebformContentCreatorInterface::ELEMENTS);
  }

  /**
   * Check if synchronization between nodes and webform submissions is used.
   *
   * @return bool
   *   true, when the synchronization is used. Otherwise, returns false.
   */
  public function getSyncEditContentCheck() {
    return $this->get(WebformContentCreatorInterface::SYNC_CONTENT);
  }

  /**
   * Check if synchronization is used in deletion.
   *
   * @return bool
   *   true, when the synchronization is used. Otherwise, returns false.
   */
  public function getSyncDeleteContentCheck() {
    return $this->get(WebformContentCreatorInterface::SYNC_CONTENT_DELETE);
  }

  /**
   * Get node field in which the webform submission id will be stored.
   *
   * @return string
   *   Field machine name.
   */
  public function getSyncContentField() {
    return $this->get(WebformContentCreatorInterface::SYNC_CONTENT_FIELD);
  }

  /**
   * Returns the encryption method.
   *
   * @return bool
   *   true, when an encryption profile is used. Otherwise, returns false.
   */
  public function getEncryptionCheck() {
    return $this->get(WebformContentCreatorInterface::USE_ENCRYPT);
  }

  /**
   * Returns the encryption profile.
   *
   * @return string
   *   The encryption profile name.
   */
  public function getEncryptionProfile() {
    return $this->get(WebformContentCreatorInterface::ENCRYPTION_PROFILE);
  }

  /**
   * Get node title.
   *
   * @return string
   *   Node title.
   */
  private function getNodeTitle() {
    // Get title.
    if ($this->get(WebformContentCreatorInterface::FIELD_TITLE) !== NULL && $this->get(WebformContentCreatorInterface::FIELD_TITLE) !== '') {
      $nodeTitle = $this->get(WebformContentCreatorInterface::FIELD_TITLE);
    }
    else {
      $nodeTitle = \Drupal::entityTypeManager()->getStorage(WebformContentCreatorInterface::WEBFORM)->load($this->get(WebformContentCreatorInterface::WEBFORM))->label();
    }

    return $nodeTitle;
  }

  /**
   * Get encryption profile name.
   *
   * @return string
   *   Encryption profile name.
   */
  private function getProfileName() {
    $encryptionProfile = '';
    $useEncrypt = $this->get(WebformContentCreatorInterface::USE_ENCRYPT);
    if ($useEncrypt) {
      $encryptionProfile = \Drupal::service('entity.manager')->getStorage(WebformContentCreatorInterface::ENCRYPTION_PROFILE)->load($this->getEncryptionProfile());
    }

    return $encryptionProfile;
  }

  /**
   * Get decrypted value with the configured encryption profile.
   *
   * @param string $value
   *   Encrypted value.
   * @param string $encryptionProfile
   *   Encryption profile name.
   *
   * @return string
   *   Encryption profile used to encrypt/decrypt $value
   */
  private function getDecryptionFromProfile($value, $encryptionProfile = '') {
    if ($this->getEncryptionCheck()) {
      $decValue = WebformContentCreatorUtilities::getDecryptedValue($value, $encryptionProfile);
    }
    else {
      $decValue = $value;
    }
    return $decValue;
  }

  /**
   * Use a single mapping to set a Node field value.
   *
   * @param \Drupal\node\NodeInterface $initialContent
   *   Content being mapped with a webform submission.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission entity.
   * @param array $fields
   *   Node fields.
   * @param array $data
   *   Webform submission data.
   * @param string $encryptionProfile
   *   Encryption profile used in Webform encrypt module.
   * @param string $fieldId
   *   Node field id.
   * @param array $mapping
   *   Single mapping between node and webform submissions.
   * @param array $attributes
   *   All mapping values between Node and Webform submission values.
   *
   * @return \Drupal\node\NodeInterface
   *   Created node.
   */
  private function mapNodeField(NodeInterface $initialContent, WebformSubmissionInterface $webform_submission, array $fields = [], array $data = [], $encryptionProfile = '', $fieldId = '', array $mapping = [], array $attributes = []) {
    $content = $initialContent;
    if (!$content->hasField($fieldId) || !is_array($mapping)) {
      return $content;
    }
    if ($attributes[$fieldId][WebformContentCreatorInterface::CUSTOM_CHECK]) {
      $decValue = WebformContentCreatorUtilities::getDecryptedTokenValue($mapping[WebformContentCreatorInterface::CUSTOM_VALUE], $encryptionProfile, $webform_submission);
      if ($decValue === 'true' || $decValue === 'TRUE') {
        $decValue = TRUE;
      }
    }
    else {
      if (!$attributes[$fieldId][WebformContentCreatorInterface::TYPE]) {
        if (!array_key_exists(WebformContentCreatorInterface::WEBFORM_FIELD, $mapping) || !array_key_exists($mapping[WebformContentCreatorInterface::WEBFORM_FIELD], $data)) {
          return $content;
        }
        $decValue = $this->getDecryptionFromProfile($data[$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]], $encryptionProfile);
        if ($fields[$fieldId]->getType() === 'entity_reference' && (!is_array($decValue) && intval($decValue) === 0)) {
          $content->set($fieldId, []);
          return $content;
        }
      }
      else {
        $fieldObject = $webform_submission->{$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]};
        if ($fieldObject instanceof EntityReferenceFieldItemList) {
          $decValue = $webform_submission->{$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]}->getValue()[0]['target_id'];
        }
        else {
          $decValue = $webform_submission->{$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]}->value;
        }
      }
    }

    if ($fields[$fieldId]->getType() == 'datetime') {
      $decValue = $this->convertTimestamp($decValue, $fields, $fieldId);
    }

    // Check if field's max length is exceeded.
    $maxLength = $this->checkMaxFieldSizeExceeded($fields, $fieldId, $decValue);
    if ($maxLength === 0) {
      $content->set($fieldId, $decValue);
    }
    else {
      $content->set($fieldId, substr($decValue, 0, $maxLength));
    }

    return $content;
  }

  /**
   * Create node from webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   */
  public function createNode(WebformSubmissionInterface $webform_submission) {
    $nodeTitle = $this->getNodeTitle();

    // Get webform submission data.
    $data = $webform_submission->getData();
    if (empty($data)) {
      return 0;
    }

    $encryptionProfile = $this->getProfileName();

    // Decrypt title.
    $decryptedTitle = WebformContentCreatorUtilities::getDecryptedTokenValue($nodeTitle, $encryptionProfile, $webform_submission);

    // Decode HTML entities, returning them to their original UTF-8 characters.
    $decodedTitle = Html::decodeEntities($decryptedTitle);

    // Create new node.
    $content = Node::create([
      WebformContentCreatorInterface::TYPE => $this->getContentType(),
      'title' => $decodedTitle,
    ]);

    // Set node fields values.
    $attributes = $this->get(WebformContentCreatorInterface::ELEMENTS);

    $contentType = \Drupal::entityTypeManager()->getStorage('node_type')->load($this->getContentType());
    if (empty($contentType)) {
      return FALSE;
    }

    $fields = WebformContentCreatorUtilities::contentTypeFields($contentType);
    if (empty($fields)) {
      return FALSE;
    }
    foreach ($attributes as $k2 => $v2) {
      $content = $this->mapNodeField($content, $webform_submission, $fields, $data, $encryptionProfile, $k2, $v2, $attributes);
    }

    $result = FALSE;

    // Save node.
    try {
      $result = $content->save();
    }
    catch (\Exception $e) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($this->t('A problem occurred when creating a new node.'));
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($e->getMessage());
    }
    return $result;
  }

  /**
   * Update node from webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   * @param string $op
   *   Operation.
   *
   * @return bool
   *   True, if succeeded. Otherwise, return false.
   */
  public function updateNode(WebformSubmissionInterface $webform_submission, $op = 'edit') {
    if (empty($this->getSyncContentField())) {
      return FALSE;
    }

    $contentType = \Drupal::entityTypeManager()->getStorage('node_type')->load($this->getContentType());
    if (empty($contentType)) {
      return FALSE;
    }

    $fields = WebformContentCreatorUtilities::contentTypeFields($contentType);
    if (empty($fields)) {
      return FALSE;
    }

    if (!array_key_exists($this->getSyncContentField(), $fields)) {
      return FALSE;
    }

    $nodeTitle = $this->getNodeTitle();

    // Get webform submission data.
    $data = $webform_submission->getData();
    if (empty($data)) {
      return FALSE;
    }

    $encryptionProfile = $this->getProfileName();

    // Decrypt title.
    $decryptedTitle = WebformContentCreatorUtilities::getDecryptedTokenValue($nodeTitle, $encryptionProfile, $webform_submission);

    // Decode HTML entities, returning them to their original UTF-8 characters.
    $decodedTitle = Html::decodeEntities($decryptedTitle);

    // Get nodes created from this webform submission.
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([$this->getSyncContentField() => $webform_submission->id()]);

    // Use only first result, if exists.
    if (!($content = reset($nodes))) {
      return FALSE;
    }

    if ($op === 'delete' && !empty($this->getSyncDeleteContentCheck())) {
      $result = $content->delete();
      return $result;
    }

    if (empty($this->getSyncEditContentCheck())) {
      return FALSE;
    }

    // Set title.
    $content->setTitle($decodedTitle);

    // Set node fields values.
    $attributes = $this->get(WebformContentCreatorInterface::ELEMENTS);

    foreach ($attributes as $k2 => $v2) {
      $content = $this->mapNodeField($content, $webform_submission, $fields, $data, $encryptionProfile, $k2, $v2, $attributes);
    }

    $result = FALSE;

    // Save node.
    try {
      $result = $content->save();
    }
    catch (\Exception $e) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($this->t('A problem occurred while updating node.'));
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($e->getMessage());
    }

    return $result;

  }

  /**
   * Check if the content type entity exists.
   *
   * @return bool
   *   True, if content type entity exists. Otherwise, returns false.
   */
  public function existsContentType() {
    // Get content type id.
    $content_type_id = $this->getContentType();

    // Get content type entity.
    $content_type_entity = \Drupal::entityTypeManager()->getStorage('node_type')->load($content_type_id);
    return !empty($content_type_entity);
  }

  /**
   * Check if the content type id is equal to the configured content type.
   *
   * @param string $ct
   *   Content type id.
   *
   * @return bool
   *   True, if the parameter is equal to the content type id of Webform
   *   content creator entity. Otherwise, returns false.
   */
  public function equalsContentType($ct) {
    return $ct === $this->getContentType();
  }

  /**
   * Check if the webform id is equal to the configured webform id.
   *
   * @param string $webform
   *   Webform id.
   *
   * @return bool
   *   True, if the parameter is equal to the webform id of Webform
   *   content creator entity. Otherwise, returns false.
   */
  public function equalsWebform($webform) {
    return $webform === $this->getWebform();
  }

  /**
   * Show a message accordingly to status, after creating/updating an entity.
   *
   * @param int $status
   *   Status int, returned after creating/updating an entity.
   */
  public function statusMessage($status) {
    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label entity.', ['%label' => $this->getTitle()]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label entity was not saved.', ['%label' => $this->getTitle()]));
    }
  }

  /**
   * Check if field maximum size is exceeded.
   *
   * @param array $fields
   *   Content type fields.
   * @param string $k
   *   Field machine name.
   * @param string $decValue
   *   Decrypted value.
   *
   * @return int
   *   1 if maximum size is exceeded, otherwise return 0.
   */
  public function checkMaxFieldSizeExceeded(array $fields, $k, $decValue = "") {
    if (!array_key_exists($k, $fields) || empty($fields[$k])) {
      return 0;
    }
    $fieldSettings = $fields[$k]->getSettings();
    if (empty($fieldSettings) || !array_key_exists('max_length', $fieldSettings)) {
      return 0;
    }

    $maxLength = $fieldSettings['max_length'];
    if (empty($maxLength)) {
      return 0;
    }
    if ($maxLength < strlen($decValue)) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->notice($this->t('Problem: Field max length exceeded (truncated).'));
      return $maxLength;
    }
    return strlen($decValue);
  }

  /**
   * Convert timestamp value according to field type.
   *
   * @param int $datefield
   *   Original datetime value.
   * @param array $fields
   *   Content type fields.
   * @param int $fieldId
   *   Field machine name id.
   *
   * @return Timestamp
   *   Converted value.
   */
  public function convertTimestamp($datefield, array $fields, $fieldId) {
    $dateTime = new DrupalDateTime($datefield, 'UTC');
    $dateType = $fields[$fieldId]->getSettings()['datetime_type'];
    if ($dateType === 'datetime') {
      $formatted = \Drupal::service('date.formatter')->format(
        $dateTime->getTimestamp(), 'custom',
        DateTimeItemInterface::DATETIME_STORAGE_FORMAT, 'UTC'
      );
    }
    else {
      $formatted = \Drupal::service('date.formatter')->format(
        $dateTime->getTimestamp(), 'custom', 
        DateTimeItemInterface::DATE_STORAGE_FORMAT, 'UTC'
      );
    }

    return $formatted;
  }

}
