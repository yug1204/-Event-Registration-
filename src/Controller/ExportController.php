<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_registration\Service\EventRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for exporting registrations as CSV.
 */
class ExportController extends ControllerBase {

  /**
   * The event repository service.
   *
   * @var \Drupal\event_registration\Service\EventRepository
   */
  protected $repository;

  /**
   * Constructs an ExportController object.
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
   * Exports registrations as CSV.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The CSV file response.
   */
  public function exportCsv(Request $request) {
    // Get filters from query parameters.
    $filters = [];
    if ($request->query->has('event_date')) {
      $filters['event_date'] = $request->query->get('event_date');
    }
    if ($request->query->has('event_id')) {
      $filters['event_id'] = $request->query->get('event_id');
    }

    // Get registrations based on filters.
    $registrations = $this->repository->getRegistrations($filters);

    // Create CSV content.
    $csv_data = [];

    // Add header row.
    $csv_data[] = [
      'ID',
      'Full Name',
      'Email',
      'College Name',
      'Department',
      'Category',
      'Event Date',
      'Event ID',
      'Submission Date',
    ];

    // Add data rows.
    foreach ($registrations as $registration) {
      $csv_data[] = [
        $registration->id,
        $registration->full_name,
        $registration->email,
        $registration->college_name,
        $registration->department,
        $registration->category,
        $registration->event_date,
        $registration->event_id,
        date('Y-m-d H:i:s', $registration->created),
      ];
    }

    // Generate CSV content.
    $csv_content = '';
    foreach ($csv_data as $row) {
      $csv_content .= implode(',', array_map(function($field) {
        // Escape fields that contain commas, quotes, or newlines.
        if (strpos($field, ',') !== FALSE || strpos($field, '"') !== FALSE || strpos($field, "\n") !== FALSE) {
          return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
      }, $row)) . "\n";
    }

    // Create response.
    $response = new Response($csv_content);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="event_registrations_' . date('Y-m-d_His') . '.csv"');

    return $response;
  }

}
