# ArenaX PWA + Tournament Admin

PHP 8.1+ / MySQL 8 or MariaDB / Tailwind CSS / Web App Manifest / Service Worker. Mobile-first installable PWA with account registration/login, player UI, wallet, manual UPI top-up request submission, admin review, tournament creation/status management, tournament listing/join endpoint, coin ledger, player moderation, tickets and audit log.

## Setup
1. Use PHP 8.1+ with PDO MySQL and Fileinfo. Enable HTTPS.
2. Create a MySQL database and user in cPanel/phpMyAdmin. Import `schema.sql` (if DB already selected and cannot create DB, remove `CREATE DATABASE` and `USE` lines).
3. Edit `config.php` credentials and base URL. Never commit credentials.
4. Upload project contents to the web root or a subfolder. Ensure `private_uploads/` is writable by PHP. Best practice is to move it outside document root and update upload/evidence paths.
5. Create the first admin from CLI: `php setup-admin.php admin@example.com 'Use-A-Long-Unique-Password' 'ArenaX Admin'`. Delete `setup-admin.php` immediately after use. Do not run this from a public browser.
6. Visit `/admin/login.php`. Configure real UPI instructions in player UI/config before accepting any money. Coin package prices are server-authoritative in `api/topup-request.php`.
7. Test with dummy users and zero-value test tournaments before launch.

## PWA install
- Android Chrome/Edge: open HTTPS domain; use Install App prompt or menu > Install app/Add to Home screen. Prompt availability varies by browser/install eligibility.
- iPhone/iPad: Safari > Share > Add to Home Screen. iOS does not expose `beforeinstallprompt`.
- Desktop Chrome/Edge: install icon in address bar or browser menu when eligible.

## Implemented admin sections
Dashboard; payment requests with approve/reject; tournament creation and status changes; player status moderation; coin ledger; support ticket list; audit log; settings guidance. Admin roles: super_admin, finance, tournament, support, moderator, analyst. Current page actions enforce role checks for finance, tournament, and moderation tasks. Extend permissions before production.

## Payment safety
Screenshots are untrusted evidence and must never be treated as payment proof. Finance admin must verify receipt against bank/UPI merchant records independently. Approval locks the pending request in a DB transaction, inserts a unique ledger entry, updates coins and sends a notification. Duplicate UTRs are rejected. Never credit coins client-side. A static UPI QR/ID is a manual process, not automated reconciliation. Refunds, payouts, GST/tax, state-specific gaming rules, age restrictions, and applicable Indian online-gaming/payment regulations require professional review before launch.

## Important limitations / production checklist
This is a substantial deployable starter, not a fully audited commercial service. Before public launch add MFA, rate limiting, email verification/password reset, stronger CSP and security headers, upload malware scanning, private storage outside webroot, image evidence preview tests, support ticket actions, team roster and bracket automation, result evidence/review and prize distribution, push subscription/delivery service, payment reconciliation integration, backups, monitoring, privacy/terms/consent, accessibility review, load/security testing, and legal review. Use compiled Tailwind assets instead of CDN in production. Service worker caches only the app shell; authenticated and financial API responses must remain network-only.
