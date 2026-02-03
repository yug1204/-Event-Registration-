# Event Registration Module

A custom Drupal 10 module that provides comprehensive event registration functionality with email notifications, admin management, and CSV export capabilities.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Database Schema](#database-schema)
- [Form URLs](#form-urls)
- [Validation Logic](#validation-logic)
- [Email Notifications](#email-notifications)
- [Permissions](#permissions)
- [Troubleshooting](#troubleshooting)

## Features

- **Event Configuration**: Admin interface to create and manage events
- **Public Registration Form**: User-facing form with AJAX-enhanced cascading dropdowns
- **Duplicate Prevention**: Email + Event Date combination uniqueness validation
- **Email Notifications**: Automatic confirmation emails to users and admins
- **Admin Listing**: Filter, view, and analyze registrations
- **CSV Export**: Download filtered registration data
- **Dependency Injection**: Follows Drupal best practices with no hard-coded service calls
- **PSR-4 Autoloading**: Proper namespace and directory structure
- **Drupal Coding Standards**: Fully compliant with Drupal coding standards

## Requirements

- Drupal 10.x
- PHP 7.4 or higher
- Working mail system (SMTP module recommended for production)

## Installation

### Step 1: Copy Module Files

Copy the `event_registration` folder to your Drupal installation:

```bash
cp -r event_registration /path/to/drupal/web/modules/custom/
```

### Step 2: Enable the Module

Using Drush:

```bash
drush en event_registration -y
drush cr
```

Or via the Drupal admin interface:
1. Navigate to **Extend** (`/admin/modules`)
2. Find "Event Registration" in the list
3. Check the checkbox next to it
4. Click "Install"

### Step 3: Verify Installation

Check that the database tables were created:

```bash
drush sqlq "SHOW TABLES LIKE 'event_registration%'"
```

You should see:
- `event_registration_events`
- `event_registration_submissions`

### Step 4: Configure Permissions

1. Navigate to **People → Permissions** (`/admin/people/permissions`)
2. Assign the following permissions as needed:
   - **Administer event registration** - For admins who can create events and configure settings
   - **View event registrations** - For users who can view the admin listing page
   - **Access event registration form** - For users who can submit registrations (typically Authenticated users or Anonymous users)

## Configuration

### Admin Settings

1. Navigate to **Configuration → System → Event Registration Settings** (`/admin/config/event-registration/settings`)

2. Configure the following:
   - **Admin Notification Email Address**: Email where admin notifications will be sent
   - **Enable Admin Notifications**: Toggle to enable/disable admin email notifications

3. Click **Save configuration**

### Create Events

1. Navigate to **Configuration → System → Event Configuration** (`/admin/config/event-registration/events`)

2. Fill in the event details:
   - **Event Registration Start Date**: When registration opens
   - **Event Registration End Date**: When registration closes
   - **Event Date**: The actual event date
   - **Event Name**: Name of the event
   - **Category**: Select from:
     - Online Workshop
     - Hackathon
     - Conference
     - One-day Workshop

3. Click **Save Event Configuration**

**Note**: The registration form will only display events that are currently within their registration period (between start and end dates).

## Usage

### For Users: Registering for Events

1. Navigate to **Event Registration** (`/event-registration/register`)

2. Fill in your details:
   - Full Name
   - Email Address
   - College Name
   - Department

3. Select event details using the cascading dropdowns:
   - **Category**: Choose the event category (this will populate available dates)
   - **Event Date**: Select the date (this will populate available events)
   - **Event Name**: Select the specific event

4. Click **Register**

5. You will receive a confirmation email with your registration details

### For Admins: Viewing Registrations

1. Navigate to **Content → Event Registrations** (`/admin/event-registration/registrations`)

2. Use the filters:
   - **Event Date**: Select a date to filter registrations
   - **Event Name**: Select a specific event (populated based on selected date)

3. View the participant count and registration table

4. Click **Export as CSV** to download the filtered data

## Database Schema

### Table: `event_registration_events`

Stores event configuration details.

| Column | Type | Description |
|--------|------|-------------|
| `id` | serial | Primary key |
| `registration_start_date` | varchar(20) | Registration opening date (YYYY-MM-DD) |
| `registration_end_date` | varchar(20) | Registration closing date (YYYY-MM-DD) |
| `event_date` | varchar(20) | Actual event date (YYYY-MM-DD) |
| `event_name` | varchar(255) | Name of the event |
| `category` | varchar(100) | Event category |
| `created` | int | Unix timestamp of creation |

**Indexes**:
- `category` - For faster category-based queries
- `event_date` - For faster date-based queries

### Table: `event_registration_submissions`

Stores user registration submissions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | serial | Primary key |
| `full_name` | varchar(255) | Registrant's full name |
| `email` | varchar(255) | Registrant's email address |
| `college_name` | varchar(255) | College name |
| `department` | varchar(255) | Department |
| `category` | varchar(100) | Event category |
| `event_date` | varchar(20) | Event date (YYYY-MM-DD) |
| `event_id` | int | Foreign key to `event_registration_events.id` |
| `created` | int | Unix timestamp of submission |

**Indexes**:
- `email_event_date` - Composite index for duplicate detection
- `event_id` - Foreign key index for joins

## Form URLs

| Form/Page | URL | Permission Required |
|-----------|-----|---------------------|
| Event Configuration | `/admin/config/event-registration/events` | administer event registration |
| Event Registration Form | `/event-registration/register` | access event registration form |
| Admin Settings | `/admin/config/event-registration/settings` | administer event registration |
| Admin Listing | `/admin/event-registration/registrations` | view event registrations |
| CSV Export | `/admin/event-registration/export-csv` | view event registrations |

## Validation Logic

### Registration Form Validation

1. **Email Format Validation**
   - Uses PHP's `filter_var()` with `FILTER_VALIDATE_EMAIL`
   - Ensures valid email addresses are submitted
   - Error message: "Please enter a valid email address."

2. **Special Characters Validation**
   - Applied to: Full Name, College Name, Department, Event Name
   - Regex pattern: `/^[a-zA-Z0-9\s.\',\-]+$/`
   - Allows: Letters, numbers, spaces, periods, commas, apostrophes, hyphens
   - Blocks: Special characters like `!@#$%^&*()_+={}[]|\:;"<>?/`
   - Error message: "[Field] contains invalid special characters. Only letters, numbers, spaces, and basic punctuation are allowed."

3. **Duplicate Registration Prevention**
   - Checks for existing registration with same email + event date combination
   - Query: `SELECT COUNT(*) FROM event_registration_submissions WHERE email = ? AND event_date = ?`
   - Prevents users from registering twice for events on the same date
   - Error message: "You have already registered for an event on this date with this email address."

4. **Date Range Validation**
   - Ensures registration end date is on or after start date
   - Validation: `strtotime($end_date) >= strtotime($start_date)`
   - Error message: "Registration end date must be on or after the start date."

5. **Required Field Validation**
   - All form fields are marked as required
   - Drupal's Form API automatically validates required fields

### AJAX Validation

The form uses AJAX callbacks to dynamically populate dropdowns:

1. **Category Selection** → Triggers `updateEventDates()`
   - Fetches event dates for selected category
   - Only shows dates for events currently open for registration

2. **Event Date Selection** → Triggers `updateEventNames()`
   - Fetches event names for selected category and date
   - Populates dropdown with available events

## Email Notifications

### User Confirmation Email

**Trigger**: Sent immediately after successful registration

**Recipient**: User's email address from the registration form

**Subject**: "Event Registration Confirmation"

**Content**:
```
Dear [Full Name],

Thank you for registering for our event. Here are your registration details:

Event Name: [Event Name]
Event Date: [Event Date]
Category: [Category]
College: [College Name]
Department: [Department]

We look forward to seeing you at the event!

Best regards,
Event Management Team
```

### Admin Notification Email

**Trigger**: Sent immediately after successful registration (if enabled in settings)

**Recipient**: Admin email address from configuration

**Subject**: "New Event Registration Received"

**Content**:
```
A new event registration has been received.

Registration Details:
--------------------
Name: [Full Name]
Email: [Email]
College: [College Name]
Department: [Department]

Event Details:
-------------
Event Name: [Event Name]
Event Date: [Event Date]
Category: [Category]

Registration Time: [Current Timestamp]
```

### Email Configuration

The module uses Drupal's core Mail API. For production environments:

1. Install and configure an SMTP module (e.g., `smtp` or `symfony_mailer`)
2. Configure mail settings in `settings.php` or via the SMTP module's settings
3. Test email sending using Drupal's mail test functionality

## Permissions

The module defines three custom permissions:

### 1. Administer event registration

- **Label**: "Administer event registration"
- **Description**: "Manage event configurations and settings"
- **Restricts access**: Yes (marked as administrative permission)
- **Typical roles**: Administrator
- **Grants access to**:
  - Event Configuration form
  - Admin Settings form

### 2. View event registrations

- **Label**: "View event registrations"
- **Description**: "Access the admin listing page to view registrations"
- **Restricts access**: No
- **Typical roles**: Administrator, Event Manager
- **Grants access to**:
  - Admin Listing page
  - CSV Export functionality

### 3. Access event registration form

- **Label**: "Access event registration form"
- **Description**: "Submit event registrations"
- **Restricts access**: No
- **Typical roles**: Authenticated user, Anonymous user
- **Grants access to**:
  - Public registration form

## Troubleshooting

### Issue: No events appear in the registration form

**Cause**: No events are currently within their registration period

**Solution**:
1. Check that events have been created via `/admin/config/event-registration/events`
2. Verify that today's date is between the registration start and end dates
3. Check the database: `drush sqlq "SELECT * FROM event_registration_events"`

### Issue: Emails are not being sent

**Cause**: Drupal's mail system is not configured

**Solution**:
1. Check Drupal's mail configuration
2. Install and configure an SMTP module
3. Test email sending: `drush php-eval "mail('test@example.com', 'Test', 'Test message');"`
4. Check admin settings to ensure notifications are enabled: `/admin/config/event-registration/settings`
5. Verify admin email address is configured

### Issue: AJAX dropdowns not working

**Cause**: JavaScript not enabled or caching issues

**Solution**:
1. Clear Drupal cache: `drush cr`
2. Ensure JavaScript is enabled in the browser
3. Check browser console for JavaScript errors
4. Disable page caching for testing: `/admin/config/development/performance`

### Issue: Duplicate registration validation not working

**Cause**: Database index may not be properly created

**Solution**:
1. Reinstall the module:
   ```bash
   drush pmu event_registration -y
   drush en event_registration -y
   drush cr
   ```
2. Verify the index exists:
   ```bash
   drush sqlq "SHOW INDEX FROM event_registration_submissions WHERE Key_name = 'email_event_date'"
   ```

### Issue: CSV export is empty

**Cause**: No filters selected or no data matches filters

**Solution**:
1. Ensure you've selected at least an event date or event name
2. Check that registrations exist for the selected filters
3. Query the database directly:
   ```bash
   drush sqlq "SELECT * FROM event_registration_submissions WHERE event_date = '2026-02-10'"
   ```

### Issue: Special characters validation too strict

**Cause**: Some names or institutions use characters not in the allowed pattern

**Solution**:
If you need to allow additional characters, modify the regex pattern in `RegistrationValidator::validateTextField()`:

```php
// Current pattern
return (bool) preg_match('/^[a-zA-Z0-9\s.\',\-]+$/', $text);

// Example: Allow parentheses and forward slashes
return (bool) preg_match('/^[a-zA-Z0-9\s.\',\-()\/]+$/', $text);
```

## Technical Architecture

### Service Layer (Dependency Injection)

The module uses three services defined in `event_registration.services.yml`:

1. **event_registration.repository** (`EventRepository`)
   - Handles all database operations
   - Injects: `@database`

2. **event_registration.validator** (`RegistrationValidator`)
   - Handles validation logic
   - Injects: `@event_registration.repository`

3. **event_registration.email** (`EmailService`)
   - Handles email sending
   - Injects: `@plugin.manager.mail`, `@config.factory`

### Dependency Injection Pattern

All forms and controllers use dependency injection via the `create()` method:

```php
public static function create(ContainerInterface $container) {
  return new static(
    $container->get('event_registration.repository'),
    $container->get('event_registration.validator'),
    $container->get('event_registration.email')
  );
}
```

**No** `\Drupal::service()` calls exist in business logic, adhering to Drupal best practices.

### PSR-4 Autoloading

The module follows PSR-4 autoloading standards:

- Namespace: `Drupal\event_registration\`
- Base directory: `src/`
- Structure:
  - `src/Form/` → Forms
  - `src/Service/` → Services
  - `src/Controller/` → Controllers

## Architecture Decisions

### Custom Tables vs. Entity API

**Decision**: Use custom database tables (`event_registration_events`, `event_registration_submissions`) operated via a Repository pattern instead of Drupal's node/entity system.

**Reasoning**: 
1. **Performance**: For a high-volume registration system, custom tables offer leaner SQL queries without the overhead of the Entity Field API.
2. **Schema Control**: Full control over column types and indexing strategies (e.g., Composite index on `email` + `event_date`).
3. **Simplicity**: The requirements are transactional rather than content-heavy. We do not need revisions, translations, or field UI for these records.

### Dependency Injection

**Decision**: Strict Use of Dependency Injection.

**Reasoning**:
- Removes hidden dependencies (`\Drupal::service()`).
- Makes the classes (especially `EventRegistrationForm` and validators) easily testable with PHPUnit mocks.
- Follows modern Drupal 10 best practices.

### AJAX Implementation

Forms use Drupal's Form API `#ajax` property:

```php
'#ajax' => [
  'callback' => '::updateEventDates',
  'wrapper' => 'event-date-wrapper',
  'event' => 'change',
],
```

Callbacks return render arrays that replace the specified wrapper element.

## Development Notes

### Adding New Event Categories

To add new event categories, modify the options in two places:

1. `EventConfigurationForm::buildForm()`
2. Update the dropdown options array

### Extending Validation

To add custom validation rules:

1. Add methods to `RegistrationValidator` service
2. Call the new validation methods in form `validateForm()` methods

### Customizing Email Templates

Email templates are defined in:
- `EmailService::buildUserEmailBody()`
- `EmailService::buildAdminEmailBody()`

Modify these methods to customize email content and formatting.

## Module Structure

```
event_registration/
├── config/
│   └── install/
│       └── event_registration.settings.yml
├── src/
│   ├── Controller/
│   │   ├── ExportController.php
│   │   └── RegistrationListController.php
│   ├── Form/
│   │   ├── AdminSettingsForm.php
│   │   ├── EventConfigurationForm.php
│   │   └── EventRegistrationForm.php
│   └── Service/
│       ├── EmailService.php
│       ├── EventRepository.php
│       └── RegistrationValidator.php
├── event_registration.info.yml
├── event_registration.install
├── event_registration.links.menu.yml
├── event_registration.module
├── event_registration.permissions.yml
├── event_registration.routing.yml
├── event_registration.services.yml
└── README.md
```

## Support

For issues, questions, or contributions, please refer to the module's issue queue or contact the maintainer.

## License

This module is licensed under the GPL-2.0-or-later license, consistent with Drupal core.
