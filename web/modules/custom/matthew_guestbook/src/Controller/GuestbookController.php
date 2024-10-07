<?php

namespace Drupal\matthew_guestbook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\matthew_guestbook\Service\GuestbookService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\matthew_guestbook\Service\GuestbookService $guestbook_service
   *   The guestbook service to handle database operations.
   */
  public function __construct(
    RequestStack $request_stack,
    GuestbookService $guestbook_service,
  ) {
    $this->requestStack = $request_stack;
    $this->guestbookService = $guestbook_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GuestbookController|static {
    return new static(
      $container->get('request_stack'),
      $container->get('matthew.guestbook_service'),
    );
  }

  /**
   * Displays the guestbook entries form.
   *
   * @return array
   *   A render array for the guestbook entries form.
   */
  public function content(): array {
    // Get the current request from the request stack.
    $current_request = $this->requestStack->getCurrentRequest();

    // Get the 'page' query parameter, defaulting to 0 if not set.
    $page = $current_request->query->get('page', 0);

    // Validate and sanitize the page number.
    $page = intval($page);

    // Get the total number of pages.
    $total_pages = $this->guestbookService->getLastPage();

    // Handle invalid page numbers.
    if ($page < 0) {
      // If the page is less than 0, redirect to page 0.
      $this->guestbookService->redirectToPage('<current>', ['page' => 0]);
    }
    elseif ($page > $total_pages) {
      // If page exceeds total, go to last.
      $this->guestbookService->redirectToPage('<current>', ['page' => $total_pages]);
    }

    // Load guestbook entries with pagination.
    $entries = $this->guestbookService->getPaginatedGuestbookEntries();

    // Iterate through each entry and process the necessary data.
    foreach ($entries as $entry) {
      // Load the avatar image or set a default avatar.
      $avatar_id = $entry->get('avatar')->target_id;
      $entry->rendered_avatar = $avatar_id
        ? $this->guestbookService->getMediaFileRenderArray($avatar_id, 'field_avatar_image', 'matthew_guestbook_avatar')
        : $this->guestbookService->getDefaultAvatarRenderArray($entry->get('name')->value);

      // Load the review image.
      $review_image_id = $entry->get('review_image')->target_id;
      $entry->rendered_review_image = $this->guestbookService->getMediaFileRenderArray(
        $review_image_id,
        'field_review_image',
        'matthew_guestbook_review'
      );

      // Format the created date.
      $entry->formatted_created_date = $this->guestbookService->formatDate(
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
