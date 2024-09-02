<?php

namespace Drupal\matthew_guestbook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for handling guestbook entries.
 */
class GuestbookController extends ControllerBase {

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FormBuilderInterface $form_builder,
    FileUrlGeneratorInterface $file_url_generator,
    ModuleExtensionList $module_extension_list,
    DateFormatterInterface $date_formatter,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->fileUrlGenerator = $file_url_generator;
    $this->moduleExtensionList = $module_extension_list;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GuestbookController|static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('file_url_generator'),
      $container->get('extension.list.module'),
      $container->get('date.formatter')
    );
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
  protected function getMediaFileRenderArray(mixed $media_id, string $field_name, string $image_style): array {
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
  protected function getDefaultAvatarRenderArray(string $name): array {
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
   * Displays the guestbook entries form.
   *
   * @return array
   *   A render array for the guestbook entries form.
   */
  public function content(): array {
    $entries = $this->entityTypeManager->getStorage('guestbook_entry')->loadMultiple();

    foreach ($entries as $entry) {
      $avatar_id = $entry->get('avatar')->target_id;
      $entry->avatar_render_array = $avatar_id
        ? $this->getMediaFileRenderArray($avatar_id, 'field_avatar_image', 'matthew_guestbook_avatar')
        : $this->getDefaultAvatarRenderArray($entry->get('name')->value);

      $review_image_id = $entry->get('review_image')->target_id;
      $entry->review_image_render_array = $this->getMediaFileRenderArray(
        $review_image_id,
        'field_review_image',
        'matthew_guestbook_review'
      );

      $entry->formatted_created_date = $this->dateFormatter->format(
        $entry->get('created')->value,
        'matthew_guestbook_date_format'
      );

    }

    return [
      '#theme' => 'guestbook-entries',
      '#entries' => $entries,
      '#attached' => [
        'library' => [
          'matthew_guestbook/guestbook_entries',
        ],
      ],
      '#cache' => [
        'tags' => ['view'],
        'contexts' => ['user'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides an edit form for a guestbook entry.
   *
   * @param int $id
   *   The ID of the guestbook entry to edit.
   *
   * @return array
   *   A render array for the edit form.
   */
  public function edit($id) {
    return [
      '#markup' => $this->t('Edit functionality for entry ID: @id', ['@id' => $id]),
    ];
  }

  /**
   * Provides a delete form for a guestbook entry.
   *
   * @param int $id
   *   The ID of the guestbook entry to delete.
   *
   * @return array
   *   A render array for the delete form.
   */
  public function delete($id) {
    return [
      '#markup' => $this->t('Delete functionality for entry ID: @id', ['@id' => $id]),
    ];
  }

}
