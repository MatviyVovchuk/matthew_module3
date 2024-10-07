<?php

namespace Drupal\matthew_guestbook\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\matthew_guestbook\Service\GuestbookService;
use Drupal\matthew_guestbook\Traits\GuestbookFormTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a custom Guestbook form.
 */
class AddGuestbookRecordForm extends FormBase {
  use GuestbookFormTrait;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The guestbook service.
   *
   * @var \Drupal\matthew_guestbook\Service\GuestbookService
   */
  protected $guestbookService;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\matthew_guestbook\Service\GuestbookService $guestbook_service
   *   The guestbook service to handle database operations.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(
    GuestbookService $guestbook_service,
    LoggerInterface $logger,
    FormBuilderInterface $form_builder,
  ) {
    $this->guestbookService = $guestbook_service;
    $this->logger = $logger;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AddGuestbookRecordForm|static {
    return new static(
      $container->get('matthew.guestbook_service'),
      $container->get('logger.channel.default'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'guestbook_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Add form fields.
    $form['#id'] = $this->getFormId();

    // Use form traits to render form fields.
    $this->traitBuildForm($form, $form_state);

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

  /**
   * AJAX form submission handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function submitFormAjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    try {
      $values = $form_state->getValues();

      // Create a new guestbook entry.
      $this->guestbookService->addEntry([
        'name' => $values['name'],
        'email' => $values['email'],
        'phone' => $values['phone'],
        'message' => $values['message'],
        'review' => $values['review'],
        'avatar' => $values['avatar'],
        'review_image' => $values['review_image'],
      ]);

      // Display success message.
      $response->addCommand(new MessageCommand(
        $this->t('%name, your entry has been saved.', [
          '%name' => $form_state->getValue('name'),
        ]),
        NULL,
        ['type' => 'status']
      ));

      // Reset form state and rebuild the form.
      $form_state->setRebuild();
      $form_state->setValues([]);
      $form_state->setUserInput([]);

      // Rebuild and replace the form.
      $rebuilt_form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
      $response->addCommand(new ReplaceCommand('#' . $this->getFormId(), $rebuilt_form));
    }
    catch (\Exception $e) {
      // Error logging for developers.
      $this->logger('matthew_guestbook')->error('Error saving guestbook entry: @message', [
        '@message' => $e->getMessage(),
      ]);

      // Display an error message to the user.
      $response->addCommand(new MessageCommand(
        $this->t('An error occurred while saving your entry. Please try again later.'),
        NULL,
        ['type' => 'error']
      ));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This function can be left empty as we are handling submission via AJAX.
  }

}
