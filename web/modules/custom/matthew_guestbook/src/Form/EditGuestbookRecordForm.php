<?php

namespace Drupal\matthew_guestbook\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\matthew_guestbook\Traits\GuestbookFormTrait;

/**
 * Implements a custom Guestbook edit form.
 */
class EditGuestbookRecordForm extends FormBase {
  use GuestbookFormTrait;

  /**
   * The ID of the cat record.
   *
   * @var int
   */
  protected int $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'guestbook_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array {
    // Set the ID of the cat record to be edited.
    $this->id = $id;

    // Add form fields.
    $this->traitBuildForm($form, $form_state);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Check if $id is available.
    if (!empty($this->id)) {
      // Load the record from the database.
      $record = $this->guestbookService->getGuestbookEntries(['id' => $this->id], TRUE);

      // Set default values for form elements.
      if ($record) {
        $form['name']['#default_value'] = $record->get('name')->value;
        $form['email']['#default_value'] = $record->get('email')->value;
        $form['phone']['#default_value'] = $record->get('phone')->value;
        $form['message']['#default_value'] = $record->get('message')->value;
        $form['review']['#default_value'] = $record->get('review')->value;

        // If there is a avatar_mid,
        // load the file entity and set it as the default value.
        if (!empty($record->get('avatar')->target_id)) {
          $form['avatar']['#default_value'] = $record->get('avatar')->target_id;
        }

        // If there is a review_image_mid,
        // load the file entity and set it as the default value.
        if (!empty($record->get('review_image')->target_id)) {
          $form['review_image']['#default_value'] = $record->get('review_image')->target_id;
        }
      }
    }

    return $form;
  }

  /**
   * AJAX form submission handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Prepare new data.
    $avatar_media_id = $form_state->getValue('avatar');
    $review_image_media_id = $form_state->getValue('review_image');

    try {
      // Update cat record.
      $this->guestbookService->updateGuestbookEntry($this->id, [
        'name' => $form_state->getValue('name'),
        'email' => $form_state->getValue('email'),
        'phone' => $form_state->getValue('phone'),
        'message' => $form_state->getValue('message'),
        'review' => $form_state->getValue('review'),
        'avatar' => !empty($avatar_media_id) ? $avatar_media_id : NULL,
        'review_image' => !empty($review_image_media_id) ? $review_image_media_id : NULL,
      ]);

      // Display a status message and redirect to the cats list.
      $this->messenger()->addStatus($this->t('The guestbook entry has been updated.'));

      // Redirect to the guestbook page.
      $form_state->setRedirect('matthew_guestbook.page');
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to update guestbook entry with ID @id. Error: @message', [
        '@id' => $this->id,
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('Failed to update the guestbook entry. Please try again later.'));
    }

  }

}
