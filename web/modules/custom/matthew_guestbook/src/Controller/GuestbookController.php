<?php

namespace Drupal\matthew_guestbook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\matthew_guestbook\Service\GuestbookService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for handling guestbook entries.
 */
class GuestbookController extends ControllerBase {
  /**
   * The guestbook service.
   *
   * @var \Drupal\matthew_guestbook\Service\GuestbookService
   */
  protected $guestbookService;

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
   * @param \Drupal\matthew_guestbook\Service\GuestbookService $guestbook_service
   *    The guestbook service to handle database operations.
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
    GuestbookService $guestbook_service,
    EntityTypeManagerInterface $entity_type_manager,
    FormBuilderInterface $form_builder,
    FileUrlGeneratorInterface $file_url_generator,
    ModuleExtensionList $module_extension_list,
    DateFormatterInterface $date_formatter,
  ) {
    $this->guestbookService = $guestbook_service;
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
      $container->get('matthew.guestbook_service'),
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('file_url_generator'),
      $container->get('extension.list.module'),
      $container->get('date.formatter')
    );
  }

  /**
   * Displays the guestbook entries form.
   *
   * @return array
   *   A render array for the guestbook entries form.
   */
  public function content(): array {
    // Load all guestbook entries.
    $entries = $this->entityTypeManager->getStorage('guestbook_entry')->loadMultiple();

    // Iterate through each entry and process the necessary data.
    foreach ($entries as $entry) {
      // Load the avatar image or set a default avatar.
      $avatar_id = $entry->get('avatar')->target_id;
      $entry->avatar_render_array = $avatar_id
        ? $this->guestbookService->getMediaFileRenderArray($avatar_id, 'field_avatar_image', 'matthew_guestbook_avatar')
        : $this->guestbookService->getDefaultAvatarRenderArray($entry->get('name')->value);

      // Load the review image.
      $review_image_id = $entry->get('review_image')->target_id;
      $entry->review_image_render_array = $this->guestbookService->getMediaFileRenderArray(
        $review_image_id,
        'field_review_image',
        'matthew_guestbook_review'
      );

      // Format the created date.
      $entry->formatted_created_date = $this->dateFormatter->format(
        $entry->get('created')->value,
        'matthew_guestbook_date_format'
      );

      // Generate the mailto and tel links.
      $entry->social_links = [
        'email' => $this->guestbookService->buildLink(
          $entry->get('email')->value,
          'mailto:' . $entry->get('email')->value,
          ['class' => ['mail-phone-link']]
        ),
        'phone' => $this->guestbookService->buildLink(
          $entry->get('phone')->value,
          'tel:' . $entry->get('phone')->value,
          ['class' => ['mail-phone-link']]
        ),
      ];

      // Generate the edit and delete action links.
      $entry->management_links = [
        'edit' => $this->guestbookService->buildLink(
          $this->t('Edit'),
          \Drupal\Core\Url::fromRoute('matthew_guestbook.edit', ['id' => $entry->id()]),
          ['class' => ['button', 'button--action', 'button--primary', 'edit-button']]
        ),
        'delete' => $this->guestbookService->buildLink(
          $this->t('Delete'),
          \Drupal\Core\Url::fromRoute('matthew_guestbook.delete', ['id' => $entry->id()]),
          ['class' => ['button', 'button--action', 'button--danger', 'delete-button']]
        ),
      ];
    }

    // Return the render array to be used in the template.
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

}
