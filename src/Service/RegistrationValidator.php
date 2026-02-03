<?php

namespace Drupal\event_registration\Service;

/**
 * Validation service for event registration forms.
 */
class RegistrationValidator {

  /**
   * The event repository service.
   *
   * @var \Drupal\event_registration\Service\EventRepository
   */
  protected $repository;

  /**
   * Constructs a RegistrationValidator object.
   *
   * @param \Drupal\event_registration\Service\EventRepository $repository
   *   The event repository service.
   */
  public function __construct(EventRepository $repository) {
    $this->repository = $repository;
  }

  /**
   * Validates email format.
   *
   * @param string $email
   *   The email address to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateEmail($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  /**
   * Validates text field for special characters.
   *
   * @param string $text
   *   The text to validate.
   *
   * @return bool
   *   TRUE if valid (no special characters), FALSE otherwise.
   */
  public function validateTextField($text) {
    // Allow letters, numbers, spaces, and basic punctuation (. , - ').
    return (bool) preg_match('/^[a-zA-Z0-9\s.\',\-]+$/', $text);
  }

  /**
   * Checks if a registration is a duplicate.
   *
   * @param string $email
   *   The email address.
   * @param string $event_date
   *   The event date.
   *
   * @return bool
   *   TRUE if duplicate, FALSE otherwise.
   */
  public function isDuplicateRegistration($email, $event_date) {
    return $this->repository->checkDuplicateRegistration($email, $event_date);
  }

  /**
   * Validates that end date is after start date.
   *
   * @param string $start_date
   *   The start date.
   * @param string $end_date
   *   The end date.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateDateRange($start_date, $end_date) {
    return strtotime($end_date) >= strtotime($start_date);
  }

}
