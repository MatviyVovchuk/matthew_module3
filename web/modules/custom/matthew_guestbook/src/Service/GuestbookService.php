<?php

namespace Drupal\matthew_guestbook\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * Constructs a new GuestbookService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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

}
