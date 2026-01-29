# Security Checklist - laravel-whatsapp Package

Use this checklist before deploying to production.

## Critical Security Items (MUST FIX)

- [ ] **Update Guzzle dependency** to version 7.5 or higher
  ```bash
  composer require guzzlehttp/guzzle:^7.5
  ```

- [ ] **Implement webhook signature verification**
  - Add `app_secret` to WhatsApp configuration
  - Verify `X-Hub-Signature-256` header in webhook controller
  - Reject requests with invalid signatures

- [ ] **Disable test routes in production**
  ```env
  WHATSAPP_ENABLE_TEST_ROUTES=false
  ```

## High Priority Security Items

- [ ] **Add rate limiting to webhook endpoint**
  ```php
  Route::post('/whatsapp', [...])
      ->middleware(['throttle:60,1']);
  ```

- [ ] **Sanitize logging to exclude PII**
  - Remove phone numbers from logs
  - Remove message content from logs
  - Log only essential metadata

- [ ] **Add input validation to webhook handler**
  - Validate phone number format
  - Validate message types
  - Validate timestamp format
  - Sanitize all user input

- [ ] **Secure test routes**
  - Add authentication middleware
  - Add authorization checks
  - Log all test route usage

## Medium Priority Security Items

- [ ] **Implement phone number validation**
  - Validate E.164 format
  - Sanitize phone number input
  - Document required format

- [ ] **Add message size validation**
  - Check 4096 character limit for text
  - Validate media URL lengths
  - Return clear error messages

- [ ] **Review token storage**
  - Use Laravel encryption for tokens if stored in DB
  - Document token rotation procedures
  - Implement token expiration

- [ ] **Add webhook deduplication**
  - Check for duplicate message IDs
  - Implement idempotency keys
  - Prevent duplicate processing

## Configuration Security

- [ ] **Environment variables are set correctly**
  ```env
  WHATSAPP_PHONE_ID=your_phone_id
  WHATSAPP_TOKEN=your_token
  WHATSAPP_VERIFY_TOKEN=secure_random_string
  WHATSAPP_APP_SECRET=your_app_secret  # NEW - for signature verification
  ```

- [ ] **Production .env is secured**
  - Not committed to version control
  - Proper file permissions (600)
  - Encrypted backups

- [ ] **Test routes disabled**
  ```env
  WHATSAPP_ENABLE_TEST_ROUTES=false
  APP_DEBUG=false
  ```

## Deployment Security

- [ ] **HTTPS is enforced** on all endpoints
- [ ] **Firewall rules** restrict webhook access if needed
- [ ] **Monitoring is set up** for suspicious activity
- [ ] **Error logging** is configured properly
- [ ] **Log rotation** is configured
- [ ] **Database backups** are encrypted and secure

## Data Protection (GDPR/Privacy)

- [ ] **Data retention policy** is defined and documented
- [ ] **PII is minimized** in logs and database
- [ ] **User consent** is obtained for storing messages
- [ ] **Data deletion** procedures are implemented
- [ ] **Privacy policy** covers WhatsApp data handling

## Testing Security

- [ ] **Penetration testing** has been performed
- [ ] **Webhook forgery** has been tested and blocked
- [ ] **Rate limiting** has been tested
- [ ] **Input validation** has been tested
- [ ] **Error handling** has been tested

## Monitoring & Incident Response

- [ ] **Failed webhook verifications** are logged and alerted
- [ ] **Unusual activity** is monitored
- [ ] **Rate limit violations** trigger alerts
- [ ] **Incident response plan** is documented
- [ ] **Security contact** is designated

## Documentation

- [ ] **Security best practices** are documented
- [ ] **Webhook signature verification** is documented
- [ ] **Production deployment guide** includes security steps
- [ ] **Token rotation procedures** are documented
- [ ] **Incident response procedures** are documented

## Dependencies

- [ ] **All dependencies** are up to date
- [ ] **Vulnerability scanning** is automated in CI/CD
- [ ] **Dependency updates** are regularly reviewed
- [ ] **composer.lock** is committed

## Code Quality

- [ ] **Static analysis** tools are run (PHPStan, Psalm)
- [ ] **Code review** has been performed
- [ ] **Security review** has been performed
- [ ] **All tests pass** including security tests

---

## Quick Production Deployment Checklist

Before going live, ensure:

1. ✅ Guzzle updated to 7.5+
2. ✅ Webhook signature verification implemented
3. ✅ Test routes disabled
4. ✅ Rate limiting added
5. ✅ Logs sanitized
6. ✅ Input validation added
7. ✅ HTTPS enforced
8. ✅ Monitoring configured
9. ✅ All environment variables set
10. ✅ Security testing completed

---

## Emergency Contacts

**Security Issues:** [Your security team contact]  
**WhatsApp Support:** [Meta Business Support]  
**On-Call Engineer:** [Your on-call contact]

---

Last Updated: January 29, 2026  
Next Review: After critical fixes are implemented
