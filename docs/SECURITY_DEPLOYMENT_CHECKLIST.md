# ABN Security Deployment Checklist

## 1. Secrets and Environment
- Keep production secrets outside the web root.
- Preferred env file path: `/home/lesley-tabi/.abn.env`.
- File permissions: `chmod 600 /home/lesley-tabi/.abn.env`.
- Optional override: set `ABN_ENV_FILE=/absolute/path/to/env` in server environment.
- Never commit real credentials into Git.

## 2. Web Server Hardening
- Enforce HTTPS for production domains.
- Keep `.htaccess` dotfile deny rules enabled.
- Disable directory listing on web root.
- Restrict access to admin area by IP where possible (`ADMIN_ALLOWED_IPS`).

## 3. Application Security
- Ensure CSRF tokens are present for all POST forms.
- Keep login and password-reset rate limits active.
- Keep `session.cookie_httponly=1`, `session.cookie_samesite=Lax`.
- In production HTTPS, set `FORCE_HTTPS=1`.

## 4. Database Security
- Use a dedicated DB user with least privileges.
- Do not use MySQL `root` in production.
- Rotate DB passwords periodically.
- Keep SQL backups encrypted and access-controlled.

## 5. SMTP and Email
- Use SMTP app passwords or provider API keys via env file only.
- Rotate SMTP credentials regularly.
- Monitor failed send attempts and auth errors.

## 6. File Upload Security
- Keep upload script execution blocked in `uploads/.htaccess` and subfolders.
- Validate MIME and extension server-side (already implemented).
- Monitor upload sizes and free disk space.

## 7. Monitoring and Logging
- Send PHP errors to server logs, not end users.
- Monitor repeated login failures and contact/reset abuse.
- Keep web server access/error logs for incident review.

## 8. Backup and Recovery
- Daily DB backup (automated).
- Weekly full site backup (code + uploads + DB dump).
- Test restore process at least monthly.

## 9. Update and Patch Cadence
- Keep PHP and dependencies patched.
- Review `composer` packages for vulnerabilities periodically.
- Re-run smoke/security checks after each deployment.

## 10. Production Go-Live Quick Check
- [ ] `/.env` is not accessible via browser.
- [ ] `FORCE_HTTPS=1` enabled.
- [ ] Admin IP allowlist configured.
- [ ] SMTP credentials loaded from external env file.
- [ ] Login, contact, forgot-password rate limits tested.
- [ ] Backup job verified.
