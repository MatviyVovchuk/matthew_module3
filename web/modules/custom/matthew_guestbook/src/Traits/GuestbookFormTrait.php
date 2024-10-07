<?php

namespace Drupal\matthew_guestbook\Traits;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
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

  /**
   * Validates the input and adds AJAX commands to the response.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The AJAX response.
   * @param string $message
   *   The validation message.
   * @param string $selector
   *   The CSS selector.
   * @param bool $is_valid
   *   The validation status.
   */
  protected function addValidationResponse(
    AjaxResponse $response,
    string $message,
    string $selector,
    bool $is_valid,
  ): void {
    $response->addCommand(new MessageCommand($this->t('@message', ['@message' => $message]), NULL, ['type' => $is_valid ? 'status' : 'error']));

    // Determine the classes to add and remove.
    $success_class = 'validation-success';
    $error_class = 'validation-error';

    // Remove both classes from the element.
    $response->addCommand(new InvokeCommand($selector, 'removeClass', [$success_class]));
    $response->addCommand(new InvokeCommand($selector, 'removeClass', [$error_class]));

    // Add the appropriate class based on validation status.
    $response->addCommand(new InvokeCommand($selector, 'addClass', [$is_valid ? $success_class : $error_class]));
  }

  /**
   * AJAX callback to validate the name.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function validateNameAjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $name = $form_state->getValue('name');
    if (mb_strlen($name, 'UTF-8') < 2) {
      $this->addValidationResponse($response, $this->t('The name must be at least 2 characters long.'), '[name="name"]', FALSE);
    }
    else {
      $this->addValidationResponse($response, $this->t('The name is valid.'), '[name="name"]', TRUE);
    }
    return $response;
  }

  /**
   * AJAX callback to validate the email.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function validateEmailAjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $email = $form_state->getValue('email');
    $email_pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    if (empty($email)) {
      $this->addValidationResponse($response, $this->t('The email is required.'), '[name="email"]', FALSE);
    }
    elseif (!preg_match($email_pattern, $email)) {
      $this->addValidationResponse($response, $this->t('The email is not valid.'), '[name="email"]', FALSE);
    }
    else {
      $this->addValidationResponse($response, $this->t('The email is valid.'), '[name="email"]', TRUE);
    }

    return $response;
  }

  /**
   * AJAX callback to validate the phone number.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function validatePhoneAjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $phone = $form_state->getValue('phone');

    // Regex pattern for validating phone number.
    $pattern = '/(7|8|9)\d{9}/';

    if (!preg_match($pattern, $phone)) {
      $this->addValidationResponse($response, $this->t('The phone number must contain only digits and not exceed 12 characters.'), '[name="phone"]', FALSE);
    }
    else {
      $this->addValidationResponse($response, $this->t('The phone number is valid.'), '[name="phone"]', TRUE);
    }

    return $response;
  }

  /**
   * AJAX callback to validate the message.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function validateMessageAjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $message = $form_state->getValue('message');

    if (empty($message)) {
      $this->addValidationResponse($response, $this->t('The message cannot be empty.'), '[name="message"]', FALSE);
    }
    else {
      $this->addValidationResponse($response, $this->t('The message is valid.'), '[name="message"]', TRUE);
    }
    return $response;
  }

  /**
   * AJAX callback to validate the review.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function validateReviewAjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $message = $form_state->getValue('review');

    if (empty($message)) {
      $this->addValidationResponse($response, $this->t('The message cannot be empty.'), '[name="review"]', FALSE);
    }
    else {
      $this->addValidationResponse($response, $this->t('The message is valid.'), '[name="review"]', TRUE);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $this->validateNameAjaxCallback($form, $form_state);
    $this->validateEmailAjaxCallback($form, $form_state);
    $this->validatePhoneAjaxCallback($form, $form_state);
    $this->validateMessageAjaxCallback($form, $form_state);
    $this->validateReviewAjaxCallback($form, $form_state);
  }

}
