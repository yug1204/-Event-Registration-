<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\event_registration\Service\EventRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for displaying event registrations list.
 */
class RegistrationListController extends ControllerBase {

  /**
   * The event repository service.
   *
   * @var \Drupal\event_registration\Service\EventRepository
   */
  protected $repository;

  /**
   * Constructs a RegistrationListController object.
   *
   * @param \Drupal\event_registration\Service\EventRepository $repository
   *   The event repository service.
   */
  public function __construct(EventRepository $repository) {
    $this->repository = $repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_registration.repository')
    );
  }

  /**
   * Displays the list of event registrations.
   *
   * @return array
   *   A render array.
   */
  public function listRegistrations(Request $request) {
    $build = [];

    // Get all event dates for the dropdown.
    $event_dates = $this->repository->getAllEventDates();
    $date_options = ['' => $this->t('- Select Event Date -')];
    foreach ($event_dates as $date) {
      $date_options[$date] = $date;
    }

    // Get selected values from form state or request.
    $selected_date = $request->query->get('event_date', '');
    $selected_event = $request->query->get('event_id', '');

    // Build the filter form.
    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['event-registration-filters']],
    ];

    $build['filters']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => $date_options,
      '#default_value' => $selected_date,
      '#attributes' => [
        'id' => 'event-date-filter',
        'onchange' => 'this.form.submit()',
      ],
    ];

    // Get events for selected date.
    $event_options = ['' => $this->t('- Select Event -')];
    if (!empty($selected_date)) {
      $events = $this->repository->getEventsByDate($selected_date);
      foreach ($events as $event) {
        $event_options[$event->id] = $event->event_name . ' (' . $event->category . ')';
      }
    }

    $build['filters']['event_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => $event_options,
      '#default_value' => $selected_event,
      '#attributes' => [
        'id' => 'event-name-filter',
        'onchange' => 'this.form.submit()',
      ],
    ];

    // Display participant count if event is selected.
    if (!empty($selected_event)) {
      $count = $this->repository->getRegistrationCount($selected_event);
      $event = $this->repository->getEventById($selected_event);

      $build['stats'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['event-registration-stats']],
      ];

      $build['stats']['info'] = [
        '#markup' => '<div class="messages messages--status"><strong>' . 
          $this->t('Total Participants for "@event" on @date: @count', [
            '@event' => $event->event_name,
            '@date' => $event->event_date,
            '@count' => $count,
          ]) . '</strong></div>',
      ];
    }

    // Get registrations based on filters.
    $filters = [];
    if (!empty($selected_date)) {
      $filters['event_date'] = $selected_date;
    }
    if (!empty($selected_event)) {
      $filters['event_id'] = $selected_event;
    }

    $registrations = $this->repository->getRegistrations($filters);

    // Build the registrations table.
    $build['registrations'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Event Date'),
        $this->t('College Name'),
        $this->t('Department'),
        $this->t('Submission Date'),
      ],
      '#rows' => [],
      '#empty' => $this->t('No registrations found.'),
      '#attributes' => ['class' => ['event-registrations-table']],
    ];

    foreach ($registrations as $registration) {
      $build['registrations']['#rows'][] = [
        $registration->full_name,
        $registration->email,
        $registration->event_date,
        $registration->college_name,
        $registration->department,
        date('Y-m-d H:i:s', $registration->created),
      ];
    }

    // Add export link if there are registrations and filters are applied.
    if (!empty($registrations) && (!empty($selected_date) || !empty($selected_event))) {
      $query_params = [];
      if (!empty($selected_date)) {
        $query_params['event_date'] = $selected_date;
      }
      if (!empty($selected_event)) {
        $query_params['event_id'] = $selected_event;
      }

      $build['export'] = [
        '#type' => 'link',
        '#title' => $this->t('Export as CSV'),
        '#url' => \Drupal\Core\Url::fromRoute('event_registration.export_csv', [], ['query' => $query_params]),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
        ],
      ];
    }

    // Wrap everything in a form for JavaScript submission.
    $build['#prefix'] = '<form method="get" action="' . $request->getRequestUri() . '">';
    $build['#suffix'] = '</form>';

    // Add some basic CSS.
    $build['#attached']['library'][] = 'system/admin';

    return $build;
  }

}
