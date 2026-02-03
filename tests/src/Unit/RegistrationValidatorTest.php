<?php

namespace Drupal\Tests\event_registration\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\event_registration\Service\RegistrationValidator;
use Drupal\event_registration\Service\EventRepository;

/**
 * Tests the RegistrationValidator service.
 *
 * @group event_registration
 */
class RegistrationValidatorTest extends UnitTestCase {

  /**
   * The validator service.
   *
   * @var \Drupal\event_registration\Service\RegistrationValidator
   */
  protected $validator;

  /**
   * Mock of the event repository.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $repository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // We mock the repository since the validator asks it for duplicate checks.
    $this->repository = $this->createMock(EventRepository::class);
    $this->validator = new RegistrationValidator($this->repository);
  }

  /**
   * Tests email validation logic.
   */
  public function testValidateEmail() {
    $this->assertTrue($this->validator->validateEmail('test@example.com'));
    $this->assertFalse($this->validator->validateEmail('invalid-email'));
    $this->assertFalse($this->validator->validateEmail('user@domain')); // Missing TLD
  }

  /**
   * Tests date validation logic.
   */
  public function testValidateDates() {
    // Valid: End date after start date
    $this->assertTrue($this->validator->validateDates('2026-02-01', '2026-02-28'));
    
    // Valid: Same day event
    $this->assertTrue($this->validator->validateDates('2026-03-15', '2026-03-15'));

    // Invalid: End date before start date
    $this->assertFalse($this->validator->validateDates('2026-02-28', '2026-02-01'));
  }
}
