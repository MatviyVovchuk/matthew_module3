<?php

namespace Drupal\matthew_guestbook\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Guestbook Entry entity.
 *
 * @ContentEntityType(
 *   id = "guestbook_entry",
 *   label = @Translation("Guestbook Entry"),
 *   base_table = "matthew_guestbook_entries",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class GuestbookEntry extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setRequired(TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20);

    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Message'))
      ->setRequired(TRUE);

    $fields['avatar'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Avatar'))
      ->setSettings([
        'target_type' => 'media',
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => ['avatar'],
        ],
      ]);

    $fields['review_image'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Review Image'))
      ->setSettings([
        'target_type' => 'media',
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => ['review_image'],
        ],
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entry was created.'));

    return $fields;
  }

}
