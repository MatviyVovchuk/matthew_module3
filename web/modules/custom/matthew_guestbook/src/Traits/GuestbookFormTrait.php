<?php

namespace Drupal\matthew_guestbook\Traits;

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a trait to provide form functionality.
 */
trait GuestbookFormTrait {
  const AVATAR_MAX_SIZE = 2 * 1024 * 1024;
  const REVIEW_IMAGE_MAX_SIZE = 5 * 1024 * 1024;
  const ALLOWED_EXTENSIONS = 'jpeg jpg png';

  /**
   * Define the form elements for the guestbook form.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form elements.
   */
  protected function traitBuildForm(array &$form, FormStateInterface $form_state): array {
    // Define the 'name' field with validation and AJAX callback.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Enter your full name. Must be at least 2 characters long.'),
      '#required' => TRUE,
      '#maxlength' => 100,
      '#attributes' => [
        'pattern' => '.{2,}',
      ],
      '#ajax' => [
        'event' => 'change',
        'callback' => '::validateNameAjaxCallback',
      ],
    ];

    // Define the 'email' field with validation and AJAX callback.
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Enter a valid email address.'),
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'callback' => '::validateEmailAjaxCallback',
      ],
    ];

    // Define the 'phone' field with validation and AJAX callback.
    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone Number'),
      '#description' => $this->t('Enter your phone number. Only digits are allowed and it should not exceed 12 characters.'),
      '#required' => TRUE,
      '#attributes' => [
        'maxlength' => 12,
      ],
      '#ajax' => [
        'event' => 'change',
        'callback' => '::validatePhoneAjaxCallback',
      ],
    ];

    // Define the 'message' field with validation and AJAX callback.
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Enter your message or feedback.'),
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'callback' => '::validateMessageAjaxCallback',
      ],
    ];

    // Define the 'review' field with validation and AJAX callback.
    $form['review'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Review'),
      '#description' => $this->t('Enter your review.'),
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'callback' => '::validateReviewAjaxCallback',
      ],
    ];

    // Define the 'avatar' field with file upload validation.
    $form['avatar'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Avatar'),
      '#description' => $this->t('Upload your avatar. Allowed formats: @formats. Max file size: @size.', [
        '@formats' => self::ALLOWED_EXTENSIONS,
        '@size' => format_size(self::AVATAR_MAX_SIZE),
      ]),
      '#allowed_bundles' => ['avatar'],
      '#required' => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => [self::ALLOWED_EXTENSIONS],
        'file_validate_size' => [self::AVATAR_MAX_SIZE],
      ],
    ];

    // Define the 'review_image' field with file upload validation.
    $form['review_image'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Review Image'),
      '#description' => $this->t('Upload an image for your review. Allowed formats: @formats. Max file size: @size.', [
        '@formats' => self::ALLOWED_EXTENSIONS,
        '@size' => format_size(self::REVIEW_IMAGE_MAX_SIZE),
      ]),
      '#allowed_bundles' => ['review_image'],
      '#required' => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => [self::ALLOWED_EXTENSIONS],
        'file_validate_size' => [self::REVIEW_IMAGE_MAX_SIZE],
      ],
    ];

    // Define the submit button with AJAX callback.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitFormAjaxCallback',
        'event' => 'click',
      ],
    ];

    // Attach custom library for form styling.
    $form['#attached'] = [
      'library' => [
        'matthew_guestbook/form-style',
      ],
    ];

    return $form;
  }

}
