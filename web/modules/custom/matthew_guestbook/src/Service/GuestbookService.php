<?php

namespace Drupal\matthew_guestbook\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Class GuestbookService.
 *
 * Provides service methods for managing guestbook entries.
 */
class GuestbookService {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a GuestbookService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ModuleExtensionList $module_extension_list,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * Retrieves a list of guestbook entry entities.
   *
   * @param array $conditions
   *   An associative array of conditions to filter the query, with keys as
   *   field names and values as the expected values.
   * @param bool $single
   *   Whether to fetch a single record. Defaults to FALSE.
   * @param string $order_by
   *   The field to order by. Defaults to 'created'.
   * @param string $order
   *   The sort direction ('ASC' or 'DESC'). Defaults to 'DESC'.
   * @param bool $access_check
   *   Whether to perform access checks. Defaults to TRUE.
   *
   * @return array|object|null
   *   An array of guestbook entry entities, a single entity object,
   *   or NULL if no entities found.
   */
  public function getGuestbookEntries(
    array $conditions = [],
    bool $single = FALSE,
    string $order_by = 'created',
    string $order = 'DESC',
    bool $access_check = TRUE,
  ): object|array|null {
    // Load the entity storage for guestbook_entry.
    $storage = $this->entityTypeManager->getStorage('guestbook_entry');

    // Build the query to retrieve entities.
    $query = $storage->getQuery();

    // Set access check explicitly.
    $query->accessCheck($access_check);

    // Apply conditions to the query.
    foreach ($conditions as $field => $value) {
      if (is_array($value)) {
        // Use 'IN' condition for array values.
        $query->condition($field, $value, 'IN');
      }
      else {
        // Use '=' condition for single values.
        $query->condition($field, $value);
      }
    }

    // Add sorting.
    $query->sort($order_by, $order);

    // Execute the query to get entity IDs.
    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      return NULL;
    }

    // Load the entities by their IDs.
    $entities = $storage->loadMultiple($entity_ids);

    // If a single record is requested, return the first entity.
    if ($single) {
      return reset($entities);
    }

    return $entities;
  }

  /**
   * Deletes a guestbook entry by its ID.
   *
   * @param int $id
   *   The ID of the guestbook entry to delete.
   *
   * @return bool
   *   TRUE if the entry was successfully deleted, FALSE otherwise.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteEntry(int $id): bool {
    $entity = $this->entityTypeManager->getStorage('guestbook_entry')->load($id);
    if ($entity) {
      $entity->delete();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Adds a new guestbook entry.
   *
   * @param array $fields
   *   An associative array containing the fields for the new guestbook entry.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The newly created guestbook entry entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addEntry(array $fields): EntityInterface {
    // Create the entity using the entity type manager.
    $entity = $this->entityTypeManager->getStorage('guestbook_entry')->create($fields);
    $entity->save();

    return $entity;
  }

  /**
   * Updates a guestbook entry.
   *
   * @param int $id
   *   The ID of the guestbook entry to update.
   * @param array $fields
   *   An associative array of field names and their new values.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The updated entity, or null if the entity was not found.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when there's an issue saving the entity.
   */
  public function updateGuestbookEntry(int $id, array $fields): ?EntityInterface {
    // Load the guestbook entry entity.
    $entity = $this->entityTypeManager->getStorage('guestbook_entry')->load($id);

    if (!$entity) {
      // Return null if the entity is not found.
      return NULL;
    }

    // Update the entity fields.
    foreach ($fields as $field_name => $value) {
      $entity->set($field_name, $value);
    }

    // Save the updated entity.
    $entity->save();

    return $entity;
  }

  /**
   * Get the render array for a given media entity ID.
   *
   * @param mixed $media_id
   *   The ID of the media entity, or null if the field is empty.
   * @param string $field_name
   *   The field name of the media entity.
   * @param string $image_style
   *   The image style to apply.
   *
   * @return array
   *   A render array for the image,
   *   or an empty array if the media entity or file could not be loaded.
   */
  public function getMediaFileRenderArray(mixed $media_id, string $field_name, string $image_style): array {
    if (empty($media_id)) {
      return [];
    }

    $media = $this->entityTypeManager->getStorage('media')->load($media_id);

    if ($media && $media->hasField($field_name) && !$media->get($field_name)->isEmpty()) {
      $file = $media->get($field_name)->entity;
      if ($file) {
        return [
          '#theme' => 'image_style',
          '#style_name' => $image_style,
          '#uri' => $file->getFileUri(),
          '#alt' => $media->label(),
          '#attributes' => [
            'class' => [$field_name === 'field_avatar_image' ? 'entry-avatar' : 'entry-review-image'],
          ],
        ];
      }
    }

    return [];
  }

  /**
   * Get the render array for the default avatar.
   *
   * @param string $name
   *   The name of the entry author.
   *
   * @return array
   *   A render array for the default avatar image.
   */
  public function getDefaultAvatarRenderArray(string $name): array {
    $module_path = $this->moduleExtensionList->getPath('matthew_guestbook');
    $default_avatar_path = $module_path . '/images/default_avatar.jpg';

    return [
      '#theme' => 'image',
      '#uri' => $default_avatar_path,
      '#alt' => $name . "'s default avatar",
      '#attributes' => [
        'class' => ['entry-avatar'],
      ],
    ];
  }

  /**
   * Builds a link render array.
   *
   * This method generates a parameterized link with a title, URL, and attributes.
   *
   * @param string $title
   *   The title of the link.
   * @param string|\Drupal\Core\Url $url
   *   The URL of the link (can be a string or a Drupal URL object).
   * @param array $attributes
   *   An array of HTML attributes for the link.
   *
   * @return array
   *   A render array for the link.
   */
  public function buildLink(string $title, string|\Drupal\Core\Url $url, array $attributes = []): array {
    return [
      '#type' => 'link',
      '#title' => $title,
      '#url' => is_string($url) ? \Drupal\Core\Url::fromUri($url) : $url,
      '#attributes' => $attributes,
    ];
  }

}
