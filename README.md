# ArenaX PWA + Tournament Admin

PHP 8.1+ / MySQL 8 or MariaDB / Tailwind CSS / Web App Manifest / Service Worker. This is a mobile-first installable PWA with player registration/login, wallet logic, UPI top-up requests, admin review, tournament discovery/join flows, support tickets, and the start of team management.

## Quick start
1. Use PHP 8.1+ with PDO MySQL and Fileinfo enabled.
2. Create a MySQL database and user in cPanel/phpMyAdmin.
3. Import `schema.sql` and, if required for expansion features, `schema_expansion.sql`.
4. Copy `config.example.php` to `config.php` and update your credentials and base URL.
5. Ensure `private_uploads/` is writable by PHP.
6. Create the first admin:
   `php setup-admin.php admin@example.com 'Use-A-Long-Unique-Password' 'ArenaX Admin'`
7. Delete `setup-admin.php` immediately after use.
8. Open `/admin/login.php` and continue with the admin setup.

## Security notes
- Never commit `config.php`.
- Do not trust client-submitted payment screenshots as proof without admin verification.
- Use HTTPS in production.
- Keep uploaded evidence outside the public web root when possible.

## PWA install
- Android Chrome/Edge: open the HTTPS site and choose Install app / Add to Home Screen.
- iPhone/iPad: Safari > Share > Add to Home Screen.
- Desktop Chrome/Edge: use the browser install prompt when available.

## Included core flows
- Account registration and login
- Player wallet and top-up request flow
- Live tournament listing and join flow
- Support ticket and notifications
- Team creation, invites, roster visibility, and captain/member actions
- Basic admin dashboard and moderation tools

## Production checklist
This project is a substantial deployable starter, not a fully audited production service. Before launch, add MFA, rate limiting, password resets, stronger security headers/CSP, malware scanning for uploads, better payment reconciliation, backups, monitoring, privacy/terms/consent reviews, accessibility reviews, and legal/regulatory review.

## License
This project is licensed under the MIT License. See [LICENSE](./LICENSE) for details.
