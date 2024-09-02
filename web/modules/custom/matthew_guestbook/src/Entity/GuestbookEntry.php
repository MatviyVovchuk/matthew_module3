<?php

namespace Drupal\matthew_guestbook\Entity;

use Drupal\Core\Entity\ContentEntityBase;
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
 *     "created" = "created",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\matthew_guestbook\GuestbookEntryListBuilder",
 *     "form" = {
 *       "default" = "Drupal\matthew_guestbook\Form\GuestbookEntryForm",
 *       "add" = "Drupal\matthew_guestbook\Form\GuestbookEntryForm",
 *       "edit" = "Drupal\matthew_guestbook\Form\GuestbookEntryForm",
 *       "delete" = "Drupal\matthew_guestbook\Form\GuestbookEntryDeleteForm",
 *     },
 *     "access" = "Drupal\matthew_guestbook\GuestbookEntryAccessControlHandler",
 *   },
 *   links = {
 *     "canonical" = "/guestbook/{guestbook_entry}",
 *     "add-form" = "/guestbook/add",
 *     "edit-form" = "/guestbook/{guestbook_entry}/edit",
 *     "delete-form" = "/guestbook/{guestbook_entry}/delete",
 *     "collection" = "/admin/content/guestbook",
 *   },
 * )
 */
class GuestbookEntry extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'email_mailto',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Message'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['review'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Review'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['avatar'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Avatar'))
      ->setDescription(t('User avatar image'))
      ->setSetting('target_type', 'media')
      ->setSetting('handler', 'default:media')
      ->setSetting('handler_settings', [
        'target_bundles' => ['avatar'],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'weight' => 0,
        'settings' => [
          'view_mode' => 'default',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['review_image'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Review Image'))
      ->setDescription(t('Image associated with the review'))
      ->setSetting('target_type', 'media')
      ->setSetting('handler', 'default:media')
      ->setSetting('handler_settings', [
        'target_bundles' => ['review_image'],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'weight' => 1,
        'settings' => [
          'view_mode' => 'default',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entry was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
