<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Database\Connection;

/**
 * Repository service for event registration database operations.
 */
class EventRepository {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an EventRepository object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Saves an event configuration to the database.
   *
   * @param array $event_data
   *   Array containing event configuration data.
   *
   * @return int
   *   The inserted event ID.
   */
  public function saveEvent(array $event_data) {
    return $this->database->insert('event_registration_events')
      ->fields([
        'registration_start_date' => $event_data['registration_start_date'],
        'registration_end_date' => $event_data['registration_end_date'],
        'event_date' => $event_data['event_date'],
        'event_name' => $event_data['event_name'],
        'category' => $event_data['category'],
        'created' => \time(),
      ])
      ->execute();
  }

  /**
   * Gets all active events (within registration period).
   *
   * @return array
   *   Array of event objects.
   */
  public function getActiveEvents() {
    $current_date = date('Y-m-d');

    return $this->database->select('event_registration_events', 'e')
      ->fields('e')
      ->condition('registration_start_date', $current_date, '<=')
      ->condition('registration_end_date', $current_date, '>=')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets events by category.
   *
   * @param string $category
   *   The event category.
   *
   * @return array
   *   Array of event objects.
   */
  public function getEventsByCategory($category) {
    $current_date = date('Y-m-d');

    return $this->database->select('event_registration_events', 'e')
      ->fields('e')
      ->condition('category', $category)
      ->condition('registration_start_date', $current_date, '<=')
      ->condition('registration_end_date', $current_date, '>=')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets events by category and date.
   *
   * @param string $category
   *   The event category.
   * @param string $event_date
   *   The event date.
   *
   * @return array
   *   Array of event objects.
   */
  public function getEventsByCategoryAndDate($category, $event_date) {
    $current_date = date('Y-m-d');

    return $this->database->select('event_registration_events', 'e')
      ->fields('e')
      ->condition('category', $category)
      ->condition('event_date', $event_date)
      ->condition('registration_start_date', $current_date, '<=')
      ->condition('registration_end_date', $current_date, '>=')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets unique event dates for a category.
   *
   * @param string $category
   *   The event category.
   *
   * @return array
   *   Array of event dates.
   */
  public function getEventDatesByCategory($category) {
    $current_date = date('Y-m-d');

    return $this->database->select('event_registration_events', 'e')
      ->fields('e', ['event_date'])
      ->condition('category', $category)
      ->condition('registration_start_date', $current_date, '<=')
      ->condition('registration_end_date', $current_date, '>=')
      ->distinct()
      ->execute()
      ->fetchCol();
  }

  /**
   * Gets all unique categories from active events.
   *
   * @return array
   *   Array of category names.
   */
  public function getActiveCategories() {
    $current_date = date('Y-m-d');

    return $this->database->select('event_registration_events', 'e')
      ->fields('e', ['category'])
      ->condition('registration_start_date', $current_date, '<=')
      ->condition('registration_end_date', $current_date, '>=')
      ->distinct()
      ->execute()
      ->fetchCol();
  }

  /**
   * Saves a registration submission to the database.
   *
   * @param array $registration_data
   *   Array containing registration form data.
   *
   * @return int
   *   The inserted submission ID.
   */
  public function saveRegistration(array $registration_data) {
    return $this->database->insert('event_registration_submissions')
      ->fields([
        'full_name' => $registration_data['full_name'],
        'email' => $registration_data['email'],
        'college_name' => $registration_data['college_name'],
        'department' => $registration_data['department'],
        'category' => $registration_data['category'],
        'event_date' => $registration_data['event_date'],
        'event_id' => $registration_data['event_id'],
        'created' => \time(),
      ])
      ->execute();
  }

  /**
   * Checks if a registration already exists for email + event date.
   *
   * @param string $email
   *   The email address.
   * @param string $event_date
   *   The event date.
   *
   * @return bool
   *   TRUE if duplicate exists, FALSE otherwise.
   */
  public function checkDuplicateRegistration($email, $event_date) {
    $count = $this->database->select('event_registration_submissions', 's')
      ->condition('email', $email)
      ->condition('event_date', $event_date)
      ->countQuery()
      ->execute()
      ->fetchField();

    return $count > 0;
  }

  /**
   * Gets registrations with optional filters.
   *
   * @param array $filters
   *   Optional filters (event_date, event_id).
   *
   * @return array
   *   Array of registration objects.
   */
  public function getRegistrations(array $filters = []) {
    $query = $this->database->select('event_registration_submissions', 's')
      ->fields('s');

    if (!empty($filters['event_date'])) {
      $query->condition('event_date', $filters['event_date']);
    }

    if (!empty($filters['event_id'])) {
      $query->condition('event_id', $filters['event_id']);
    }

    $query->orderBy('created', 'DESC');

    return $query->execute()->fetchAll();
  }

  /**
   * Gets the count of registrations for a specific event.
   *
   * @param int $event_id
   *   The event ID.
   *
   * @return int
   *   The count of registrations.
   */
  public function getRegistrationCount($event_id) {
    return $this->database->select('event_registration_submissions', 's')
      ->condition('event_id', $event_id)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Gets an event by ID.
   *
   * @param int $event_id
   *   The event ID.
   *
   * @return object|null
   *   The event object or NULL if not found.
   */
  public function getEventById($event_id) {
    return $this->database->select('event_registration_events', 'e')
      ->fields('e')
      ->condition('id', $event_id)
      ->execute()
      ->fetch();
  }

  /**
   * Gets all events (for admin listing).
   *
   * @return array
   *   Array of all event objects.
   */
  public function getAllEvents() {
    return $this->database->select('event_registration_events', 'e')
      ->fields('e')
      ->orderBy('event_date', 'DESC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets unique event dates from all events.
   *
   * @return array
   *   Array of event dates.
   */
  public function getAllEventDates() {
    return $this->database->select('event_registration_events', 'e')
      ->fields('e', ['event_date'])
      ->distinct()
      ->orderBy('event_date', 'DESC')
      ->execute()
      ->fetchCol();
  }

  /**
   * Gets events by date (for admin filtering).
   *
   * @param string $event_date
   *   The event date.
   *
   * @return array
   *   Array of event objects.
   */
  public function getEventsByDate($event_date) {
    return $this->database->select('event_registration_events', 'e')
      ->fields('e')
      ->condition('event_date', $event_date)
      ->execute()
      ->fetchAll();
  }

}
