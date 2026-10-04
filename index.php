<?php
require __DIR__.'/includes/bootstrap.php';
$csrf=htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#090d18">
  <meta name="description" content="ArenaX — play, compete and win.">
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="icon" href="assets/icon-192.png">
  <link rel="apple-touch-icon" href="assets/icon-192.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="ArenaX">
  <title>ArenaX — Play. Compete. Win.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html{background:#090d18;color-scheme:dark}body{padding-bottom:env(safe-area-inset-bottom)}.glass{background:rgba(22,27,44,.92);border:1px solid #292e42}.purple{background:linear-gradient(110deg,#6d28d9,#9333ea)}button:focus-visible,a:focus-visible,input:focus-visible{outline:2px solid #c4b5fd;outline-offset:2px}
  </style>
</head>
<body class="min-h-screen bg-[#090d18] text-white antialiased">
  <header class="sticky top-0 z-20 border-b border-white/10 bg-[#090d18]/95 backdrop-blur">
    <div class="mx-auto flex max-w-lg items-center justify-between px-4 py-3">
      <a href="#home" class="flex items-center gap-2 font-black text-xl"><span class="grid h-9 w-9 place-items-center rounded-xl purple">🎮</span>Arena<span class="text-violet-400">X</span></a>
      <div class="flex items-center gap-3">
        <span id="coin-pill" class="rounded-full border border-yellow-400/20 bg-yellow-400/10 px-3 py-1 text-sm text-yellow-300">0 AC</span>
        <button id="install-btn" class="hidden rounded-lg border border-violet-400/40 px-3 py-2 text-xs font-semibold text-violet-200">Install App</button>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-lg px-4 pb-28 pt-5">
    <section id="home" class="space-y-5">
      <div class="overflow-hidden rounded-3xl border border-violet-400/20 bg-gradient-to-br from-violet-950 via-[#171332] to-[#111827] p-5">
        <p class="text-xs font-bold uppercase tracking-[.2em] text-violet-300">India's gaming arena</p>
        <h1 class="mt-2 text-3xl font-black">Play. Compete.<br><span class="text-violet-400">Win.</span></h1>
        <p class="mt-2 max-w-xs text-sm text-slate-300">Join tournaments, build your team and climb the leaderboard.</p>
        <a href="#tournaments" class="mt-5 inline-flex rounded-xl purple px-5 py-3 text-sm font-bold">Explore tournaments <span class="ml-2">→</span></a>
      </div>

      <div>
        <div class="mb-3 flex items-center justify-between"><h2 class="font-bold">Choose your game</h2><span class="text-xs text-violet-300">Explore</span></div>
        <div class="grid grid-cols-4 gap-2 text-center text-xs">
          <div class="glass rounded-2xl p-3"><div class="mb-2 text-2xl">🎯</div>BGMI</div>
          <div class="glass rounded-2xl p-3"><div class="mb-2 text-2xl">🔥</div>Free Fire</div>
          <div class="glass rounded-2xl p-3"><div class="mb-2 text-2xl">⚔️</div>CODM</div>
          <div class="glass rounded-2xl p-3"><div class="mb-2 text-2xl">💥</div>Valorant</div>
        </div>
      </div>

      <div class="space-y-3">
        <div class="flex items-center justify-between"><h2 class="font-bold">Featured tournaments</h2><a href="#tournaments" class="text-xs text-violet-300">View all</a></div>
        <article class="glass rounded-2xl p-4">
          <div class="flex items-start justify-between"><div><span class="text-xs font-semibold text-emerald-300">REGISTRATION OPEN</span><h3 class="mt-1 font-bold">BGMI Daily Cup</h3><p class="mt-1 text-xs text-slate-400">Squad · TPP · 64 teams</p></div><div class="text-right"><p class="text-xs text-slate-400">Prize pool</p><p class="font-black text-yellow-300">10,000 AC</p></div></div>
          <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-3"><span class="text-xs text-slate-400">Entry: 50 AC / team</span><a href="#tournaments" class="rounded-lg purple px-4 py-2 text-xs font-bold">View details</a></div>
        </article>
      </div>

      <div class="glass rounded-2xl p-4">
        <h2 class="font-bold">Get started</h2>
        <p class="mt-1 text-sm text-slate-400">Sign in or create an account to save your profile and tournament progress.</p>
        <button id="auth-open" class="mt-3 w-full rounded-xl border border-violet-400/40 py-3 text-sm font-bold text-violet-200">Sign in / Register</button>
      </div>
    </section>

    <section id="tournaments" class="mt-8 space-y-3">
      <div class="mb-3 flex items-center justify-between"><h2 class="text-lg font-bold">Live tournaments</h2><span id="tournament-count" class="text-xs text-violet-300">0 listed</span></div>
      <div id="tournament-list" class="space-y-3"></div>
    </section>

    <section id="wallet" class="mt-8 space-y-3"><h2 class="text-lg font-bold">Wallet</h2><div class="glass rounded-2xl p-4"><p class="text-sm text-slate-400">Available coins</p><p id="wallet-coins" class="mt-1 text-3xl font-black text-violet-300">0 AC</p><button id="topup-open" class="mt-4 w-full rounded-xl purple py-3 text-sm font-bold">Add coins via UPI</button><p class="mt-2 text-xs text-slate-500">Coin top-ups remain pending until verified by an administrator. Demo UPI details must be configured before launch.</p></div><div class="glass rounded-2xl p-4"><div class="flex items-center justify-between"><h3 class="text-sm font-semibold text-violet-300">Top-up status</h3><span id="topup-status-count" class="text-[10px] uppercase tracking-[.12em] text-slate-400">0</span></div><div id="topup-status-list" class="mt-3 space-y-2 text-sm text-slate-300"></div></div><div class="glass rounded-2xl p-4"><h3 class="text-sm font-semibold text-violet-300">Transaction history</h3><div id="wallet-history" class="mt-3 space-y-2 text-sm text-slate-300"></div></div></section>
    <section id="profile" class="mt-8 space-y-3"><h2 class="text-lg font-bold">Profile</h2><div class="glass rounded-2xl p-4"><p id="profile-name" class="font-semibold">Guest player</p><p id="profile-email" class="text-xs text-slate-400">Not signed in</p><button id="logout-btn" class="mt-3 hidden rounded-lg border border-white/10 px-4 py-2 text-sm">Sign out</button></div></section>
  </main>

  <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-white/10 bg-[#0d1120]/95 backdrop-blur" style="padding-bottom:env(safe-area-inset-bottom)">
    <div class="mx-auto grid max-w-lg grid-cols-4 px-2 py-2 text-center text-[11px]">
      <a class="rounded-xl px-1 py-2 text-violet-300" href="#home">⌂<span class="mt-1 block">Home</span></a>
      <a class="rounded-xl px-1 py-2 text-slate-400" href="#tournaments">🏆<span class="mt-1 block">Tournaments</span></a>
      <a class="rounded-xl px-1 py-2 text-slate-400" href="#wallet">◈<span class="mt-1 block">Wallet</span></a>
      <a class="rounded-xl px-1 py-2 text-slate-400" href="#profile">♙<span class="mt-1 block">Profile</span></a>
    </div>
  </nav>

  <dialog id="auth-dialog" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-white/10 bg-[#151a29] p-5 text-white backdrop:bg-black/70">
    <form method="dialog"><button class="float-right text-slate-400" aria-label="Close">✕</button></form>
    <h2 class="text-xl font-bold">Your ArenaX account</h2>
    <p class="mt-1 text-xs text-slate-400">Create an account or sign in.</p>
    <div class="mt-4 space-y-3">
      <div id="username-wrap"><label class="mb-1 block text-xs text-slate-400">Username</label><input id="auth-username" class="w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" autocomplete="username"></div>
      <div><label class="mb-1 block text-xs text-slate-400">Email</label><input id="auth-email" type="email" class="w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" autocomplete="email"></div>
      <div><label class="mb-1 block text-xs text-slate-400">Password (min 10 characters)</label><input id="auth-password" type="password" class="w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" autocomplete="current-password"></div>
      <p id="auth-error" class="text-xs text-rose-300"></p>
      <button id="auth-submit" class="w-full rounded-xl purple py-3 text-sm font-bold">Create account</button>
      <button id="auth-toggle" class="w-full py-2 text-xs text-violet-300">Already registered? Sign in</button>
    </div>
  </dialog>

  <dialog id="topup-dialog" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-white/10 bg-[#151a29] p-5 text-white backdrop:bg-black/70">
    <form method="dialog"><button class="float-right text-slate-400" aria-label="Close">✕</button></form>
    <h2 class="text-xl font-bold">Add coins</h2>
    <p class="mt-1 text-xs text-slate-400">Pay to the official UPI ID, then submit your transaction proof.</p>
    <div class="mt-4 rounded-xl bg-[#0b1020] p-3"><p class="text-xs text-slate-400">UPI ID</p><p class="font-semibold">CONFIGURE_OFFICIAL_UPI</p><p class="mt-1 text-xs text-amber-300">Replace this demo placeholder before launch.</p></div>
    <form id="topup-form" class="mt-4 space-y-3">
      <label class="block text-xs text-slate-400">Coin package<select id="package" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm"><option value="5000|200">₹50 — 200 AC</option><option value="10000|500">₹100 — 500 AC</option><option value="20000|1100">₹200 — 1,100 AC</option></select></label>
      <label class="block text-xs text-slate-400">UPI transaction reference / UTR<input id="utr" required minlength="8" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" placeholder="Enter UTR"></label>
      <label class="block text-xs text-slate-400">Payment screenshot (JPG/PNG/WebP, max 5 MB)<input id="screenshot" type="file" accept="image/jpeg,image/png,image/webp" required class="mt-1 block w-full text-xs"></label>
      <p id="topup-error" class="text-xs text-rose-300"></p>
      <button class="w-full rounded-xl purple py-3 text-sm font-bold">Submit for verification</button>
    </form>
  </dialog>

  <dialog id="join-dialog" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-white/10 bg-[#151a29] p-5 text-white backdrop:bg-black/70">
    <form method="dialog"><button class="float-right text-slate-400" aria-label="Close">✕</button></form>
    <h2 class="text-xl font-bold">Join tournament</h2>
    <p class="mt-1 text-xs text-slate-400">Registering for <span id="join-tournament-name" class="font-semibold text-violet-300">this event</span></p>
    <form id="join-form" class="mt-4 space-y-3">
      <input id="join-tournament-id" type="hidden" value="0">
      <label class="block text-xs text-slate-400">Register as<select id="join-team-select" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm"><option value="">Solo player</option></select></label>
      <label class="block text-xs text-slate-400">Team name (optional)<input id="join-team-name" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" maxlength="100" placeholder="Enter your squad name"></label>
      <p id="join-error" class="text-xs text-rose-300"></p>
      <button class="w-full rounded-xl purple py-3 text-sm font-bold">Confirm registration</button>
    </form>
  </dialog>

  <section id="teams" class="mt-8 space-y-3">
    <h2 class="text-lg font-bold">Team management</h2>
    <div class="glass rounded-2xl p-4 space-y-4">
      <form id="team-create-form" class="space-y-3">
        <div class="grid gap-3 md:grid-cols-2">
          <label class="block text-xs text-slate-400">Team name<input id="team-name" required maxlength="60" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" placeholder="Team Alpha"></label>
          <label class="block text-xs text-slate-400">Game<input id="team-game" required maxlength="60" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" placeholder="BGMI"></label>
        </div>
        <label class="block text-xs text-slate-400">Description<textarea id="team-description" rows="3" maxlength="500" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" placeholder="Team motto, role split, and region."></textarea></label>
        <p id="team-error" class="text-xs text-rose-300"></p>
        <button class="w-full rounded-xl purple py-3 text-sm font-bold">Create team</button>
      </form>
      <div>
        <h3 class="text-sm font-semibold text-violet-300">My teams</h3>
        <div id="team-list" class="mt-2 space-y-2 text-sm text-slate-300"></div>
      </div>
      <div>
        <h3 class="text-sm font-semibold text-violet-300">Pending invites</h3>
        <div id="team-invite-list" class="mt-2 space-y-2 text-sm text-slate-300"></div>
      </div>
    </div>
  </section>

  <section id="support" class="mt-8 space-y-3">
    <h2 class="text-lg font-bold">Support & alerts</h2>
    <div class="glass rounded-2xl p-4 space-y-3">
      <div>
        <h3 class="text-sm font-semibold text-violet-300">Notifications</h3>
        <div id="notification-list" class="mt-2 space-y-2 text-sm text-slate-300"></div>
      </div>
      <form id="ticket-form" class="space-y-3 border-t border-white/10 pt-3">
        <label class="block text-xs text-slate-400">Subject<input id="ticket-subject" required maxlength="180" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" placeholder="Need help with a payment or match?"></label>
        <label class="block text-xs text-slate-400">Message<textarea id="ticket-message" required rows="4" maxlength="5000" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0b1020] p-3 text-sm" placeholder="Describe the issue in detail."></textarea></label>
        <p id="ticket-error" class="text-xs text-rose-300"></p>
        <button class="w-full rounded-xl purple py-3 text-sm font-bold">Send support request</button>
      </form>
      <div>
        <h3 class="text-sm font-semibold text-violet-300">Recent tickets</h3>
        <div id="ticket-list" class="mt-2 space-y-2 text-sm text-slate-300"></div>
      </div>
    </div>
  </section>

  <div id="toast" class="fixed bottom-24 left-1/2 hidden -translate-x-1/2 rounded-full bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-100 shadow-lg"></div>
  <script>window.ARENAX={csrf:<?=json_encode($csrf)?>};</script>
  <script src="assets/app.js" defer></script>
</body>
</html>
