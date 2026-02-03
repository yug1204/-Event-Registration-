<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure event registration settings.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['event_registration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('event_registration.settings');

    $form['admin_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Admin Notification Email Address'),
      '#description' => $this->t('Enter the email address where admin notifications should be sent.'),
      '#default_value' => $config->get('admin_email'),
      '#required' => FALSE,
    ];

    $form['enable_admin_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Admin Notifications'),
      '#description' => $this->t('Check this box to enable email notifications to the admin when new registrations are received.'),
      '#default_value' => $config->get('enable_admin_notifications') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate admin email if notifications are enabled.
    if ($form_state->getValue('enable_admin_notifications')) {
      $admin_email = $form_state->getValue('admin_email');
      if (empty($admin_email)) {
        $form_state->setErrorByName('admin_email', $this->t('Admin email address is required when notifications are enabled.'));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('event_registration.settings')
      ->set('admin_email', $form_state->getValue('admin_email'))
      ->set('enable_admin_notifications', $form_state->getValue('enable_admin_notifications'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
