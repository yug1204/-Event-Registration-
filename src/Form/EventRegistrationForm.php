<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\event_registration\Service\EventRepository;
use Drupal\event_registration\Service\RegistrationValidator;
use Drupal\event_registration\Service\EmailService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the event registration form for users.
 */
class EventRegistrationForm extends FormBase {

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
   * The email service.
   *
   * @var \Drupal\event_registration\Service\EmailService
   */
  protected $emailService;

  /**
   * Constructs an EventRegistrationForm object.
   *
   * @param \Drupal\event_registration\Service\EventRepository $repository
   *   The event repository service.
   * @param \Drupal\event_registration\Service\RegistrationValidator $validator
   *   The registration validator service.
   * @param \Drupal\event_registration\Service\EmailService $email_service
   *   The email service.
   */
  public function __construct(EventRepository $repository, RegistrationValidator $validator, EmailService $email_service) {
    $this->repository = $repository;
    $this->validator = $validator;
    $this->emailService = $email_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_registration.repository'),
      $container->get('event_registration.validator'),
      $container->get('event_registration.email')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check if there are any active events.
    $active_events = $this->repository->getActiveEvents();

    if (empty($active_events)) {
      $form['message'] = [
        '#markup' => '<div class="messages messages--warning">' . $this->t('No events are currently open for registration. Please check back later.') . '</div>',
      ];
      return $form;
    }

    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#description' => $this->t('Enter your full name.'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#description' => $this->t('Enter your email address.'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['college_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#description' => $this->t('Enter your college name.'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#description' => $this->t('Enter your department.'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    // Get active categories.
    $categories = $this->repository->getActiveCategories();
    $category_options = ['' => $this->t('- Select -')];
    foreach ($categories as $category) {
      $category_options[$category] = $category;
    }

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of Event'),
      '#description' => $this->t('Select the event category.'),
      '#options' => $category_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateEventDates',
        'wrapper' => 'event-date-wrapper',
        'event' => 'change',
      ],
    ];

    $form['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#description' => $this->t('Select the event date.'),
      '#options' => ['' => $this->t('- Select -')],
      '#required' => TRUE,
      '#prefix' => '<div id="event-date-wrapper">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::updateEventNames',
        'wrapper' => 'event-name-wrapper',
        'event' => 'change',
      ],
    ];

    // Populate event dates if category is selected.
    $category = $form_state->getValue('category');
    if (!empty($category)) {
      $event_dates = $this->repository->getEventDatesByCategory($category);
      $date_options = ['' => $this->t('- Select -')];
      foreach ($event_dates as $date) {
        $date_options[$date] = $date;
      }
      $form['event_date']['#options'] = $date_options;
    }

    $form['event_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#description' => $this->t('Select the event.'),
      '#options' => ['' => $this->t('- Select -')],
      '#required' => TRUE,
      '#prefix' => '<div id="event-name-wrapper">',
      '#suffix' => '</div>',
    ];

    // Populate event names if category and date are selected.
    $event_date = $form_state->getValue('event_date');
    if (!empty($category) && !empty($event_date)) {
      $events = $this->repository->getEventsByCategoryAndDate($category, $event_date);
      $event_options = ['' => $this->t('- Select -')];
      foreach ($events as $event) {
        $event_options[$event->id] = $event->event_name;
      }
      $form['event_name']['#options'] = $event_options;
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * AJAX callback to update event dates based on category.
   */
  public function updateEventDates(array &$form, FormStateInterface $form_state) {
    return $form['event_date'];
  }

  /**
   * AJAX callback to update event names based on category and date.
   */
  public function updateEventNames(array &$form, FormStateInterface $form_state) {
    return $form['event_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate full name for special characters.
    $full_name = $form_state->getValue('full_name');
    if (!$this->validator->validateTextField($full_name)) {
      $form_state->setErrorByName('full_name', $this->t('Full name contains invalid special characters. Only letters, numbers, spaces, and basic punctuation are allowed.'));
    }

    // Validate email format.
    $email = $form_state->getValue('email');
    if (!$this->validator->validateEmail($email)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }

    // Validate college name for special characters.
    $college_name = $form_state->getValue('college_name');
    if (!$this->validator->validateTextField($college_name)) {
      $form_state->setErrorByName('college_name', $this->t('College name contains invalid special characters. Only letters, numbers, spaces, and basic punctuation are allowed.'));
    }

    // Validate department for special characters.
    $department = $form_state->getValue('department');
    if (!$this->validator->validateTextField($department)) {
      $form_state->setErrorByName('department', $this->t('Department contains invalid special characters. Only letters, numbers, spaces, and basic punctuation are allowed.'));
    }

    // Check for duplicate registration (email + event date).
    $event_date = $form_state->getValue('event_date');
    if ($this->validator->isDuplicateRegistration($email, $event_date)) {
      $form_state->setErrorByName('email', $this->t('You have already registered for an event on this date with this email address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event_id = $form_state->getValue('event_name');
    $event = $this->repository->getEventById($event_id);

    $registration_data = [
      'full_name' => $form_state->getValue('full_name'),
      'email' => $form_state->getValue('email'),
      'college_name' => $form_state->getValue('college_name'),
      'department' => $form_state->getValue('department'),
      'category' => $form_state->getValue('category'),
      'event_date' => $form_state->getValue('event_date'),
      'event_id' => $event_id,
    ];

    // Save registration to database.
    $submission_id = $this->repository->saveRegistration($registration_data);

    // Send confirmation email to user.
    $this->emailService->sendConfirmationEmail($registration_data, $event);

    // Send notification to admin.
    $this->emailService->sendAdminNotification($registration_data, $event);

    $this->messenger()->addStatus($this->t('Thank you for registering! A confirmation email has been sent to @email.', ['@email' => $registration_data['email']]));
  }

}
