<?php

namespace Drupal\matthew_guestbook\Form;

use Drupal\Core\Ajax\AjaxResponse;
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
