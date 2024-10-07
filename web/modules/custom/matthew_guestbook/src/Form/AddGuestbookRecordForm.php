<?php

namespace Drupal\matthew_guestbook\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\matthew_guestbook\Traits\GuestbookFormTrait;

/**
 * Implements a custom Guestbook form.
 */
class AddGuestbookRecordForm extends FormBase {
  use GuestbookFormTrait;

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
          '%name' => $values['name'],
        ]),
        NULL,
        ['type' => 'status']
      ));

      $this->resetAndRebuildForm($form_state, $form, $this->getFormId(), $response);

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
