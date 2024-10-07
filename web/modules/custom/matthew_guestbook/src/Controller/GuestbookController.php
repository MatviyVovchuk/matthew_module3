<?php

namespace Drupal\matthew_guestbook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for handling guestbook entries.
 */
class GuestbookController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GuestbookController|static {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Displays the guestbook entries form.
   *
   * @return array
   *   A render array for the guestbook entries form.
   */
  public function content() {
    $query = Database::getConnection()->select('matthew_guestbook_entries', 'g')
      ->fields('g', ['id', 'name', 'email', 'phone', 'message__value', 'created'])
      ->orderBy('created', 'DESC');
    $results = $query->execute()->fetchAll();
    return [
      '#theme' => 'guestbook-entries',
      '#entries' => $results,
    ];
  }

  /**
   * Displays the form for adding a new guestbook entry.
   *
   * @return array
   *   A render array for the guestbook add entry form.
   */
  public function add() {
    return $this->formBuilder->getForm('Drupal\matthew_guestbook\Form\GuestbookForm');
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
