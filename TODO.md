# Future Roadmap & Technical Debt

## Planned Features
- [ ] **REST API Endpoint**: Expose registration data via JSON for mobile app integration.
- [ ] **Calendar Integration**: Generate .ics files for email attachments so users can add events to Outlook/Google Calendar.
- [ ] **Waitlist Logic**: Auto-move users from waitlist to registered when a spot opens up.

## Performance Improvements
- [ ] **Caching**: Implement render caching for the registration form block to reduce database hits on high-traffic days.
- [ ] **Queue Worker**: Move email sending to a QueueWorker to separate it from the HTTP request cycle (improves user perceived performance).

## Security Hardening
- [ ] **Rate Limiting**: Add Flood API integration to prevent spam submissions from the same IP.
- [ ] **CSRF Protection**: Double-check custom AJAX callbacks (currently handled by Form API, but verify for edge cases).

## Refactoring
- [ ] **Entities**: If requirements grow complexity, consider migrating custom tables (`event_registration_submissions`) to full Content Entities for Views compatibility.
