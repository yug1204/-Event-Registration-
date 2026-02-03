<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Email service for sending registration notifications.
 */
class EmailService {

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an EmailService object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(MailManagerInterface $mail_manager, ConfigFactoryInterface $config_factory) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Sends a confirmation email to the user.
   *
   * @param array $registration_data
   *   The registration data.
   * @param object $event
   *   The event object.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function sendConfirmationEmail(array $registration_data, $event) {
    $params = [
      'subject' => 'Event Registration Confirmation',
      'body' => $this->buildUserEmailBody($registration_data, $event),
    ];

    $result = $this->mailManager->mail(
      'event_registration',
      'user_confirmation',
      $registration_data['email'],
      'en',
      $params,
      NULL,
      TRUE
    );

    return $result['result'] ?? FALSE;
  }

  /**
   * Sends a notification email to the administrator.
   *
   * @param array $registration_data
   *   The registration data.
   * @param object $event
   *   The event object.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function sendAdminNotification(array $registration_data, $event) {
    $config = $this->configFactory->get('event_registration.settings');

    // Check if admin notifications are enabled.
    if (!$config->get('enable_admin_notifications')) {
      return TRUE;
    }

    $admin_email = $config->get('admin_email');
    if (empty($admin_email)) {
      return TRUE;
    }

    $params = [
      'subject' => 'New Event Registration Received',
      'body' => $this->buildAdminEmailBody($registration_data, $event),
    ];

    $result = $this->mailManager->mail(
      'event_registration',
      'admin_notification',
      $admin_email,
      'en',
      $params,
      NULL,
      TRUE
    );

    return $result['result'] ?? FALSE;
  }

  /**
   * Builds the email body for user confirmation.
   *
   * @param array $registration_data
   *   The registration data.
   * @param object $event
   *   The event object.
   *
   * @return string
   *   The email body.
   */
  protected function buildUserEmailBody(array $registration_data, $event) {
    $body = "Dear " . $registration_data['full_name'] . ",\n\n";
    $body .= "Thank you for registering for our event. Here are your registration details:\n\n";
    $body .= "Event Name: " . $event->event_name . "\n";
    $body .= "Event Date: " . $event->event_date . "\n";
    $body .= "Category: " . $registration_data['category'] . "\n";
    $body .= "College: " . $registration_data['college_name'] . "\n";
    $body .= "Department: " . $registration_data['department'] . "\n\n";
    $body .= "We look forward to seeing you at the event!\n\n";
    $body .= "Best regards,\n";
    $body .= "Event Management Team";

    return $body;
  }

  /**
   * Builds the email body for admin notification.
   *
   * @param array $registration_data
   *   The registration data.
   * @param object $event
   *   The event object.
   *
   * @return string
   *   The email body.
   */
  protected function buildAdminEmailBody(array $registration_data, $event) {
    $body = "A new event registration has been received.\n\n";
    $body .= "Registration Details:\n";
    $body .= "--------------------\n";
    $body .= "Name: " . $registration_data['full_name'] . "\n";
    $body .= "Email: " . $registration_data['email'] . "\n";
    $body .= "College: " . $registration_data['college_name'] . "\n";
    $body .= "Department: " . $registration_data['department'] . "\n\n";
    $body .= "Event Details:\n";
    $body .= "-------------\n";
    $body .= "Event Name: " . $event->event_name . "\n";
    $body .= "Event Date: " . $event->event_date . "\n";
    $body .= "Category: " . $registration_data['category'] . "\n\n";
    $body .= "Registration Time: " . date('Y-m-d H:i:s') . "\n";

    return $body;
  }

}
