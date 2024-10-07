<?php

namespace Drupal\matthew_guestbook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Url;
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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\matthew_guestbook\Service\GuestbookService $guestbook_service
   *   The guestbook service to handle database operations.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    GuestbookService $guestbook_service,
    DateFormatterInterface $date_formatter,
  ) {
    $this->guestbookService = $guestbook_service;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GuestbookController|static {
    return new static(
      $container->get('matthew.guestbook_service'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Displays the guestbook entries form.
   *
   * @return array
   *   A render array for the guestbook entries form.
   */
  public function content(): array {
    // Load guestbook entries with pagination.
    $entries = $this->guestbookService->getPaginatedGuestbookEntries();

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
          Url::fromRoute('matthew_guestbook.edit', ['id' => $entry->id()]),
          ['class' => ['button', 'button--action', 'button--primary', 'edit-button']]
        ),
        'delete' => $this->guestbookService->buildLink(
          $this->t('Delete'),
          Url::fromRoute('matthew_guestbook.delete', ['id' => $entry->id()]),
          ['class' => ['button', 'button--action', 'button--danger', 'delete-button']]
        ),
      ];
    }

    // Return the render array to be used in the template.
    return [
      '#theme' => 'guestbook-entries',
      '#entries' => $entries,
      '#pager' => [
        '#type' => 'pager',
      ],
      '#attached' => [
        'library' => [
          'matthew_guestbook/guestbook_entries',
        ],
      ],
      '#cache' => [
        'tags' => ['guestbook_entry_list'],
        'contexts' => ['user.permissions'],
        'max-age' => 0,
      ],
    ];
  }

}
