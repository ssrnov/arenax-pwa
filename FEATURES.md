# ArenaX feature map

This repository is a PHP/MySQL PWA foundation. It is not yet a production-ready commercial tournament platform. `schema_expansion.sql` adds database foundations for the additional modules below; database tables alone do not mean the player/admin workflows are implemented.

## Player-facing modules
- Account registration, login/logout, profile and coin balance: starter workflow exists and the auth flow has been repaired.
- Tournament discovery and solo join: live tournament data is now loaded from the database and players can register for upcoming events via the player UI.
- UPI top-up request with UTR and evidence: request workflow exists; finance admin must independently verify the transaction before approving.
- Game IDs, teams/rosters, team tournament entry, matchmaking, player stats, follows/community, referrals and reward claims: team creation, invites, roster visibility, and captain/member management actions are in place as a starter workflow; deeper tournament-entry and broader social features remain to be built.
- Match schedule/room access, result evidence and dispute flow: schema foundation added; complete lifecycle remains to be built.
- Notification preferences and web-push subscriptions: schema foundation added; service worker push handling, VAPID setup and delivery worker remain to be built.
- Installable PWA/offline app shell: manifest and service worker exist. Offline writes and private/financial API caching are intentionally not enabled.

## Admin modules
- Dashboard, payment review, tournament creation/status, player moderation, ledger, ticket list and audit log: basic admin pages exist.
- Match/bracket operations, result review/disputes, roster moderation, payouts, push campaigns, ticket replies/assignment, settings/feature flags, session management, export and deeper analytics: schema groundwork added where relevant; administrative screens/actions and permission tests remain to be built.

## Required before real-money/public launch
Configure HTTPS, DB credentials, real UPI instructions, private upload storage, backups and monitoring. Add MFA, email verification/password reset, rate limits, stronger security headers/CSP, malware scanning, push delivery, reconciliation, privacy/terms/consent and accessibility reviews. Have Indian gaming/payment rules, tax, age restrictions and prize distribution reviewed professionally. Test all wallet mutations and role permissions with adversarial cases. Never credit coins based only on a screenshot or client-submitted coin amount.
