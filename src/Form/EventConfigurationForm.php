<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\event_registration\Service\EventRepository;
use Drupal\event_registration\Service\RegistrationValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring events.
 */
class EventConfigurationForm extends FormBase {

  /**
   * The event repository service.
   *
   * @var \Drupal\event_registration\Service\EventRepository
   */
  protected $repository;

  /**
   * The registration validator service.
   *
   * @var \Drupal\event_registration\Service\RegistrationValidator
   */
  protected $validator;

  /**
   * Constructs an EventConfigurationForm object.
   *
   * @param \Drupal\event_registration\Service\EventRepository $repository
   *   The event repository service.
   * @param \Drupal\event_registration\Service\RegistrationValidator $validator
   *   The registration validator service.
   */
  public function __construct(EventRepository $repository, RegistrationValidator $validator) {
    $this->repository = $repository;
    $this->validator = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_registration.repository'),
      $container->get('event_registration.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_event_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['registration_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Registration Start Date'),
      '#description' => $this->t('The date when registration opens for this event.'),
      '#required' => TRUE,
    ];

    $form['registration_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Registration End Date'),
      '#description' => $this->t('The date when registration closes for this event.'),
      '#required' => TRUE,
    ];

    $form['event_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Date'),
      '#description' => $this->t('The actual date of the event.'),
      '#required' => TRUE,
    ];

    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#description' => $this->t('The name of the event.'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of Event'),
      '#description' => $this->t('Select the category for this event.'),
      '#options' => [
        '' => $this->t('- Select -'),
        'Online Workshop' => $this->t('Online Workshop'),
        'Hackathon' => $this->t('Hackathon'),
        'Conference' => $this->t('Conference'),
        'One-day Workshop' => $this->t('One-day Workshop'),
      ],
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Event Configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $start_date = $form_state->getValue('registration_start_date');
    $end_date = $form_state->getValue('registration_end_date');

    // Validate that end date is not before start date.
    if (!$this->validator->validateDateRange($start_date, $end_date)) {
      $form_state->setErrorByName('registration_end_date', $this->t('Registration end date must be on or after the start date.'));
    }

    // Validate event name for special characters.
    $event_name = $form_state->getValue('event_name');
    if (!$this->validator->validateTextField($event_name)) {
      $form_state->setErrorByName('event_name', $this->t('Event name contains invalid special characters. Only letters, numbers, spaces, and basic punctuation are allowed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event_data = [
      'registration_start_date' => $form_state->getValue('registration_start_date'),
      'registration_end_date' => $form_state->getValue('registration_end_date'),
      'event_date' => $form_state->getValue('event_date'),
      'event_name' => $form_state->getValue('event_name'),
      'category' => $form_state->getValue('category'),
    ];

    $event_id = $this->repository->saveEvent($event_data);

    $this->messenger()->addStatus($this->t('Event configuration saved successfully (ID: @id).', ['@id' => $event_id]));
  }

}
