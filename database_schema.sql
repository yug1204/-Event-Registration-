-- Database Schema for Event Registration Module
-- Custom tables for storing event configurations and user registrations

-- Table structure for event_registration_events
CREATE TABLE IF NOT EXISTS `event_registration_events` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique event ID',
  `registration_start_date` VARCHAR(20) NOT NULL COMMENT 'Event registration start date (YYYY-MM-DD)',
  `registration_end_date` VARCHAR(20) NOT NULL COMMENT 'Event registration end date (YYYY-MM-DD)',
  `event_date` VARCHAR(20) NOT NULL COMMENT 'Event date (YYYY-MM-DD)',
  `event_name` VARCHAR(255) NOT NULL COMMENT 'Name of the event',
  `category` VARCHAR(100) NOT NULL COMMENT 'Category of the event',
  `created` INT(11) NOT NULL DEFAULT 0 COMMENT 'Timestamp when the event was created',
  PRIMARY KEY (`id`),
  INDEX `category` (`category`),
  INDEX `event_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores event configuration details';

-- Table structure for event_registration_submissions  
CREATE TABLE IF NOT EXISTS `event_registration_submissions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique submission ID',
  `full_name` VARCHAR(255) NOT NULL COMMENT 'Full name of the registrant',
  `email` VARCHAR(255) NOT NULL COMMENT  'Email address of the registrant',
  `college_name` VARCHAR(255) NOT NULL COMMENT 'College name of the registrant',
  `department` VARCHAR(255) NOT NULL COMMENT 'Department of the registrant',
  `category` VARCHAR(100) NOT NULL COMMENT 'Event category',
  `event_date` VARCHAR(20) NOT NULL COMMENT 'Event date (YYYY-MM-DD)',
  `event_id` INT(10) UNSIGNED NOT NULL COMMENT 'Foreign key to event_registration_events.id',
  `created` INT(11) NOT NULL DEFAULT 0 COMMENT 'Timestamp when the registration was submitted',
  PRIMARY KEY (`id`),
  INDEX `email_event_date` (`email`, `event_date`),
  INDEX `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores user event registration submissions';

-- Sample data for testing (optional)
-- Uncomment the following lines to add sample events

-- INSERT INTO `event_registration_events` (`registration_start_date`, `registration_end_date`, `event_date`, `event_name`, `category`, `created`) VALUES
-- ('2026-02-01', '2026-02-28', '2026-03-15', 'AI & Machine Learning Workshop', 'Online Workshop', UNIX_TIMESTAMP()),
-- ('2026-02-01', '2026-02-28', '2026-03-15', 'Python Programming Bootcamp', 'Online Workshop', UNIX_TIMESTAMP()),
-- ('2026-02-10', '2026-03-10', '2026-03-20', 'National Hackathon 2026', 'Hackathon', UNIX_TIMESTAMP()),
-- ('2026-02-15', '2026-03-15', '2026-04-01', 'Tech Conference 2026', 'Conference', UNIX_TIMESTAMP()),
-- ('2026-02-20', '2026-03-20', '2026-04-05', 'Web Development Workshop', 'One-day Workshop', UNIX_TIMESTAMP());
