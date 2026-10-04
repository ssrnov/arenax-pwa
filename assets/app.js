let csrf = window.ARENAX.csrf || '', installPrompt = null, isRegister = true;
let currentUser = null;
let tournaments = [];
let notifications = [];
let tickets = [];
let walletHistory = [];
let topupStatus = [];
let teams = [];
let teamInvites = [];
let teamRoster = {};

const $ = id => document.getElementById(id);
const toast = msg => {
  const t = $('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 3200);
};

const formatCoins = value => `${Number(value || 0).toLocaleString()} AC`;
const formatDate = value => {
  if (!value) return 'TBA';
  const date = new Date(value.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
};

async function api(path, options = {}) {
  const res = await fetch(path, {
    credentials: 'same-origin',
    ...options,
    headers: {
      ...(options.headers || {}),
      'X-CSRF-Token': csrf,
    },
  });

  let data = {};
  try {
    data = await res.json();
  } catch (_) {
    data = {};
  }

  if (data.csrf) csrf = data.csrf;
  if (!res.ok) throw new Error(data.error || 'Request failed');
  return data;
}

function populateJoinTeamOptions() {
  const select = $('join-team-select');
  if (!select) return;
  const options = ['<option value="">Solo player</option>'];
  teams.forEach(team => {
    const isCaptain = Number(team.owner_id) === Number(currentUser?.id);
    if (isCaptain || Array.isArray(team.roster) && team.roster.some(member => Number(member.user_id) === Number(currentUser?.id) && String(member.status) === 'active')) {
      options.push(`<option value="${team.id}">${(team.name || 'Unnamed team')}</option>`);
    }
  });
  select.innerHTML = options.join('');
  if (!teams.length) {
    select.disabled = true;
    select.title = 'Create or join a team first';
    return;
  }
  select.disabled = false;
  select.title = '';
}

function renderTournamentList() {
  const list = $('tournament-list');
  if (!list) return;

  if (!tournaments.length) {
    list.innerHTML = '<article class="glass rounded-2xl p-4 text-sm text-slate-300">No tournaments are available right now. Check back soon or create one from the admin panel.</article>';
    $('tournament-count').textContent = '0 listed';
    return;
  }

  $('tournament-count').textContent = `${tournaments.length} listed`;
  list.innerHTML = tournaments.map(t => {
    const entry = Number(t.entry_coins || 0);
    const entries = Number(t.entries || 0);
    const remaining = Math.max(0, Number(t.max_teams || 0) - entries);
    const joined = Number(t.joined || 0) === 1;
    const badge = {
      draft: 'bg-slate-700 text-slate-200',
      upcoming: 'bg-emerald-500/15 text-emerald-300',
      live: 'bg-violet-500/15 text-violet-300',
      completed: 'bg-slate-700 text-slate-200',
      cancelled: 'bg-rose-500/15 text-rose-300',
    }[t.status] || 'bg-slate-700 text-slate-200';

    return `
      <article class="glass rounded-2xl p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <span class="inline-flex rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-[.18em] ${badge}">${t.status}</span>
            <h3 class="mt-2 font-bold text-lg">${(t.title || 'Untitled tournament')}</h3>
            <p class="mt-1 text-xs text-slate-400">${t.game || 'Game'} · ${t.mode || 'Squad'} · ${remaining} slots left</p>
          </div>
          <div class="text-right">
            <p class="text-xs text-slate-400">Prize pool</p>
            <p class="font-black text-yellow-300">${formatCoins(t.prize_coins || 0)}</p>
          </div>
        </div>
        <p class="mt-3 text-sm text-slate-300">${(t.description || 'No description added yet.').slice(0, 130)}${(t.description || '').length > 130 ? '…' : ''}</p>
        <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-3 text-xs text-slate-400">
          <span>Entry: ${formatCoins(entry)}</span>
          <span>${formatDate(t.starts_at)}</span>
        </div>
        <div class="mt-4 flex items-center justify-between gap-3">
          <span class="text-xs text-slate-400">${entries}/${t.max_teams || 0} registered</span>
          <button data-join-id="${t.id}" class="rounded-lg px-4 py-2 text-xs font-bold ${joined ? 'bg-slate-700 text-slate-200 cursor-not-allowed' : 'purple'}" ${joined ? 'disabled' : ''}>${joined ? 'Joined' : 'Join now'}</button>
        </div>
      </article>
    `;
  }).join('');

  list.querySelectorAll('[data-join-id]').forEach(button => {
    button.addEventListener('click', event => {
      const tournamentId = Number(event.currentTarget.getAttribute('data-join-id'));
      if (!tournamentId) return;
      const selectedTournament = tournaments.find(item => Number(item.id) === tournamentId);
      if (!selectedTournament) return;
      if (!currentUser) {
        $('auth-dialog').showModal();
        toast('Sign in to join tournaments');
        return;
      }
      $('join-tournament-id').value = String(tournamentId);
      $('join-tournament-name').textContent = selectedTournament.title || 'this event';
      populateJoinTeamOptions();
      $('join-team-select').value = '';
      $('join-team-name').value = '';
      $('join-error').textContent = '';
      $('join-dialog').showModal();
    });
  });
}

async function refreshMe() {
  try {
    const d = await api('api/me.php');
    currentUser = d.user || null;
    const u = currentUser;
    $('coin-pill').textContent = `${u?.coins ?? 0} AC`;
    $('wallet-coins').textContent = `${u?.coins ?? 0} AC`;
    $('profile-name').textContent = u?.username || 'Guest player';
    $('profile-email').textContent = u?.email || 'Not signed in';
    $('logout-btn').classList.toggle('hidden', !u);
    walletHistory = Array.isArray(d.ledger) ? d.ledger : [];
    topupStatus = Array.isArray(d.topups) ? d.topups : [];
    renderWalletHistory();
    renderTopupStatus();
    if (u) {
      await Promise.all([refreshNotifications(), refreshTickets(), refreshTeams()]);
    } else {
      notifications = [];
      tickets = [];
      teams = [];
      teamInvites = [];
      renderNotifications();
      renderTickets();
      renderTeams();
      renderTeamInvites();
    }
  } catch (e) {
    currentUser = null;
    walletHistory = [];
    topupStatus = [];
    notifications = [];
    tickets = [];
    teams = [];
    teamInvites = [];
    populateJoinTeamOptions();
    renderWalletHistory();
    renderTopupStatus();
    renderNotifications();
    renderTickets();
    renderTeams();
    renderTeamInvites();
    console.warn(e);
  }
}

function renderNotifications() {
  const list = $('notification-list');
  if (!list) return;
  if (!notifications.length) {
    list.innerHTML = '<p class="rounded-lg border border-white/10 bg-slate-900/80 p-2 text-xs text-slate-400">No alerts yet.</p>';
    return;
  }
  list.innerHTML = notifications.map(n => `
    <div class="rounded-lg border border-white/10 bg-slate-900/80 p-2">
      <p class="font-semibold text-violet-300">${(n.title || 'Notice')}</p>
      <p class="mt-1 text-xs text-slate-300">${(n.message || '')}</p>
      <p class="mt-1 text-[10px] text-slate-500">${formatDate(n.created_at)}</p>
    </div>
  `).join('');
}

function renderWalletHistory() {
  const list = $('wallet-history');
  if (!list) return;
  if (!walletHistory.length) {
    list.innerHTML = '<p class="rounded-lg border border-white/10 bg-slate-900/80 p-2 text-xs text-slate-400">No wallet activity yet.</p>';
    return;
  }
  list.innerHTML = walletHistory.map(entry => {
    const delta = Number(entry.delta || 0);
    const sign = delta >= 0 ? '+' : '-';
    return `
      <div class="rounded-lg border border-white/10 bg-slate-900/80 p-2">
        <div class="flex items-center justify-between gap-3">
          <span class="font-semibold ${delta >= 0 ? 'text-emerald-300' : 'text-rose-300'}">${sign}${Math.abs(delta).toLocaleString()} AC</span>
          <span class="text-[10px] uppercase tracking-[.12em] text-slate-400">${(entry.kind || 'adjustment')}</span>
        </div>
        <p class="mt-1 text-xs text-slate-300">${(entry.note || entry.reference || 'Wallet adjustment')}</p>
        <p class="mt-1 text-[10px] text-slate-500">${formatDate(entry.created_at)}</p>
      </div>
    `;
  }).join('');
}

function renderTopupStatus() {
  const list = $('topup-status-list');
  const count = $('topup-status-count');
  if (!list) return;
  if (!topupStatus.length) {
    list.innerHTML = '<p class="rounded-lg border border-white/10 bg-slate-900/80 p-2 text-xs text-slate-400">No top-ups submitted yet.</p>';
    if (count) count.textContent = '0';
    return;
  }
  if (count) count.textContent = String(topupStatus.length);
  list.innerHTML = topupStatus.map(item => {
    const statusClass = {
      pending: 'bg-amber-500/15 text-amber-300',
      approved: 'bg-emerald-500/15 text-emerald-300',
      rejected: 'bg-rose-500/15 text-rose-300',
    }[item.status] || 'bg-slate-700 text-slate-200';
    return `
      <div class="rounded-lg border border-white/10 bg-slate-900/80 p-2">
        <div class="flex items-center justify-between gap-3">
          <span class="font-semibold text-violet-300">₹${Number((item.amount_paise || 0) / 100).toFixed(2)}</span>
          <span class="rounded-full px-2 py-1 text-[10px] uppercase tracking-[.12em] ${statusClass}">${item.status || 'pending'}</span>
        </div>
        <p class="mt-1 text-xs text-slate-300">${Number(item.coins || 0).toLocaleString()} AC · ${item.utr || 'No UTR'}</p>
        <p class="mt-1 text-[10px] text-slate-500">${formatDate(item.created_at)}</p>
      </div>
    `;
  }).join('');
}

function renderTeams() {
  const list = $('team-list');
  if (!list) return;
  if (!teams.length) {
    list.innerHTML = '<p class="rounded-lg border border-white/10 bg-slate-900/80 p-2 text-xs text-slate-400">No teams yet. Create your first squad.</p>';
    return;
  }
  list.innerHTML = teams.map(team => {
    const roster = Array.isArray(team.roster) ? team.roster : (teamRoster[team.id] || []);
    const isCaptain = Number(team.owner_id) === Number(currentUser?.id);
    const rosterMarkup = roster.length ? roster.map(member => {
      const displayName = member.username || 'Player';
      const roleLabel = (member.member_role || 'member').toString();
      const statusLabel = member.status === 'invited' ? 'Invited' : 'Active';
      const canRemove = isCaptain && Number(member.user_id) !== Number(currentUser?.id);
      return `
        <div class="flex items-center gap-2 rounded-full border border-white/10 bg-slate-800 px-2 py-1 text-[10px] uppercase tracking-[.12em] text-slate-200">
          <span>${displayName} · ${roleLabel} · ${statusLabel}</span>
          ${canRemove ? `<button type="button" data-team-member-remove="${team.id}" data-membership-id="${member.membership_id || ''}" class="rounded-full bg-rose-900/80 px-1.5 py-0.5 font-bold text-[9px]">Remove</button>` : ''}
        </div>
      `;
    }).join('') : '<span class="text-[10px] uppercase tracking-[.12em] text-slate-500">No roster yet</span>';

    return `
      <div class="rounded-lg border border-white/10 bg-slate-900/80 p-2">
        <div class="flex items-center justify-between gap-2">
          <div>
            <p class="font-semibold text-violet-300">${team.name || 'Unnamed team'}</p>
            <p class="text-[10px] uppercase tracking-[.12em] text-slate-400">${team.game || 'Game'} · ${Number(team.members || 0)} members</p>
          </div>
          <span class="rounded-full bg-slate-700 px-2 py-1 text-[10px] uppercase tracking-[.12em] text-slate-200">${isCaptain ? 'Captain' : 'Member'}</span>
        </div>
        <p class="mt-1 text-xs text-slate-300">${(team.description || 'No description provided.').slice(0, 120)}${(team.description || '').length > 120 ? '…' : ''}</p>
        <div class="mt-2 flex flex-wrap gap-1">${rosterMarkup}</div>
        <div class="mt-2 flex gap-2">
          <button data-team-action="leave" data-team-id="${team.id}" class="flex-1 rounded-lg border border-white/10 bg-slate-800 px-3 py-2 text-[10px] font-bold uppercase tracking-[.12em]">Leave</button>
          ${isCaptain ? `<button data-team-action="disband" data-team-id="${team.id}" class="flex-1 rounded-lg bg-rose-900 px-3 py-2 text-[10px] font-bold uppercase tracking-[.12em]">Disband</button>` : ''}
        </div>
        <form class="mt-2 flex gap-2" data-invite-team="${team.id}">
          <input name="user_identifier" required maxlength="120" class="w-full rounded-lg border border-white/10 bg-[#0b1020] p-2 text-xs" placeholder="Invite username/email">
          <button class="rounded-lg bg-violet-600 px-3 py-2 text-[10px] font-bold uppercase tracking-[.12em]">Invite</button>
        </form>
      </div>
    `;
  }).join('');

  list.querySelectorAll('[data-team-action]').forEach(button => {
    button.addEventListener('click', async e => {
      const teamId = Number(e.currentTarget.getAttribute('data-team-id'));
      const action = e.currentTarget.getAttribute('data-team-action');
      if (!teamId || !action) return;
      try {
        const d = await api('api/team-member-action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: new URLSearchParams({ team_id: String(teamId), action }).toString(),
        });
        await refreshTeams();
        toast(d.message || 'Team update applied');
      } catch (err) {
        toast(err.message);
      }
    });
  });

  list.querySelectorAll('[data-team-member-remove]').forEach(button => {
    button.addEventListener('click', async e => {
      const membershipId = Number(e.currentTarget.getAttribute('data-membership-id'));
      const teamId = Number(e.currentTarget.getAttribute('data-team-member-remove'));
      if (!membershipId || !teamId) return;
      try {
        const d = await api('api/team-member-action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: new URLSearchParams({ team_id: String(teamId), membership_id: String(membershipId), action: 'remove_member' }).toString(),
        });
        await refreshTeams();
        toast(d.message || 'Member removed');
      } catch (err) {
        toast(err.message);
      }
    });
  });

  list.querySelectorAll('[data-invite-team]').forEach(form => {
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const teamId = Number(form.getAttribute('data-invite-team'));
      const identifier = form.querySelector('input').value.trim();
      if (!teamId || !identifier) return;
      try {
        const d = await api('api/team-invite.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: new URLSearchParams({ team_id: String(teamId), user_identifier: identifier }).toString(),
        });
        form.reset();
        await refreshTeams();
        toast(d.message || 'Invite sent');
      } catch (err) {
        toast(err.message);
      }
    });
  });
}

function renderTeamInvites() {
  const list = $('team-invite-list');
  if (!list) return;
  if (!teamInvites.length) {
    list.innerHTML = '<p class="rounded-lg border border-white/10 bg-slate-900/80 p-2 text-xs text-slate-400">No pending invites.</p>';
    return;
  }
  list.innerHTML = teamInvites.map(invite => `
    <div class="rounded-lg border border-white/10 bg-slate-900/80 p-2">
      <div class="flex items-center justify-between gap-2">
        <div>
          <p class="font-semibold text-violet-300">${invite.name || 'Team invite'}</p>
          <p class="text-[10px] uppercase tracking-[.12em] text-slate-400">${invite.game || 'Game'}</p>
        </div>
        <span class="rounded-full bg-slate-700 px-2 py-1 text-[10px] uppercase tracking-[.12em] text-slate-200">${invite.status || 'invited'}</span>
      </div>
      <div class="mt-2 flex gap-2">
        <button data-invite-action="accept" data-membership-id="${invite.membership_id}" class="flex-1 rounded-lg bg-emerald-700 px-3 py-2 text-[10px] font-bold uppercase tracking-[.12em]">Accept</button>
        <button data-invite-action="reject" data-membership-id="${invite.membership_id}" class="flex-1 rounded-lg bg-rose-900 px-3 py-2 text-[10px] font-bold uppercase tracking-[.12em]">Reject</button>
      </div>
    </div>
  `).join('');

  list.querySelectorAll('[data-invite-action]').forEach(button => {
    button.addEventListener('click', async e => {
      const membershipId = Number(e.currentTarget.getAttribute('data-membership-id'));
      const action = e.currentTarget.getAttribute('data-invite-action');
      if (!membershipId || !action) return;
      try {
        const d = await api('api/team-invite-action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: new URLSearchParams({ membership_id: String(membershipId), action }).toString(),
        });
        await refreshTeams();
        toast(d.message || 'Invite updated');
      } catch (err) {
        toast(err.message);
      }
    });
  });
}

function renderTickets() {
  const list = $('ticket-list');
  if (!list) return;
  if (!tickets.length) {
    list.innerHTML = '<p class="rounded-lg border border-white/10 bg-slate-900/80 p-2 text-xs text-slate-400">You have no open support tickets yet.</p>';
    return;
  }
  list.innerHTML = tickets.map(ticket => `
    <div class="rounded-lg border border-white/10 bg-slate-900/80 p-2">
      <div class="flex items-center justify-between gap-2">
        <p class="font-semibold text-violet-300">${(ticket.subject || 'Support request')}</p>
        <span class="rounded-full bg-slate-700 px-2 py-1 text-[10px] uppercase tracking-[.12em] text-slate-200">${ticket.status || 'open'}</span>
      </div>
      <p class="mt-1 text-xs text-slate-300">${(ticket.message || '').slice(0, 120)}${(ticket.message || '').length > 120 ? '…' : ''}</p>
      <p class="mt-1 text-[10px] text-slate-500">${formatDate(ticket.created_at)}</p>
    </div>
  `).join('');
}

async function refreshNotifications() {
  try {
    const d = await api('api/notifications.php');
    notifications = Array.isArray(d.notifications) ? d.notifications : [];
    renderNotifications();
  } catch (e) {
    notifications = [];
    renderNotifications();
  }
}

async function refreshTickets() {
  try {
    const d = await api('api/ticket.php');
    tickets = Array.isArray(d.tickets) ? d.tickets : [];
    renderTickets();
  } catch (e) {
    tickets = [];
    renderTickets();
  }
}

async function refreshTeams() {
  try {
    const d = await api('api/teams.php');
    teams = Array.isArray(d.teams) ? d.teams : [];
    teamInvites = Array.isArray(d.invites) ? d.invites : [];
    teamRoster = d.roster && typeof d.roster === 'object' ? d.roster : {};
    teams = teams.map(team => ({ ...team, roster: Array.isArray(team.roster) ? team.roster : (teamRoster[team.id] || []) }));
    populateJoinTeamOptions();
    renderTeams();
    renderTeamInvites();
  } catch (e) {
    teams = [];
    teamInvites = [];
    teamRoster = {};
    renderTeams();
    renderTeamInvites();
  }
}

async function refreshTournaments() {
  try {
    const d = await api('api/tournaments.php');
    tournaments = Array.isArray(d.tournaments) ? d.tournaments : [];
    renderTournamentList();
  } catch (e) {
    console.error(e);
    $('tournament-list').innerHTML = '<article class="glass rounded-2xl p-4 text-sm text-rose-300">Unable to load tournaments right now.</article>';
  }
}

if ('serviceWorker' in navigator && location.protocol === 'https:') {
  window.addEventListener('load', () => navigator.serviceWorker.register('./sw.js').catch(console.error));
}

window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  installPrompt = e;
  $('install-btn').classList.remove('hidden');
});

$('install-btn').addEventListener('click', async () => {
  if (installPrompt) {
    installPrompt.prompt();
    await installPrompt.userChoice;
    installPrompt = null;
    $('install-btn').classList.add('hidden');
  } else {
    toast('Use your browser menu and choose Add to Home Screen / Install App.');
  }
});

window.addEventListener('appinstalled', () => toast('ArenaX installed successfully!'));

$('auth-open').addEventListener('click', () => $('auth-dialog').showModal());

$('auth-toggle').addEventListener('click', () => {
  isRegister = !isRegister;
  $('username-wrap').classList.toggle('hidden', !isRegister);
  $('auth-submit').textContent = isRegister ? 'Create account' : 'Sign in';
  $('auth-toggle').textContent = isRegister ? 'Already registered? Sign in' : 'New to ArenaX? Create account';
  $('auth-password').setAttribute('autocomplete', isRegister ? 'new-password' : 'current-password');
  $('auth-error').textContent = '';
});

$('auth-submit').addEventListener('click', async e => {
  e.preventDefault();
  $('auth-error').textContent = '';
  const payload = {
    username: $('auth-username').value,
    email: $('auth-email').value,
    password: $('auth-password').value,
  };

  try {
    await api(isRegister ? 'api/register.php' : 'api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    $('auth-dialog').close();
    toast('Welcome to ArenaX!');
    await refreshMe();
    await refreshTournaments();
  } catch (err) {
    $('auth-error').textContent = err.message;
  }
});

$('logout-btn').addEventListener('click', async () => {
  try {
    await api('api/logout.php', { method: 'POST' });
    await refreshMe();
    await refreshTournaments();
    toast('Signed out');
  } catch (e) {
    toast(e.message);
  }
});

$('topup-open').addEventListener('click', async () => {
  try {
    const d = await api('api/me.php');
    if (!d.user) {
      $('auth-dialog').showModal();
      toast('Sign in to add coins');
      return;
    }
    $('topup-dialog').showModal();
  } catch (e) {
    toast(e.message);
  }
});

$('topup-form').addEventListener('submit', async e => {
  e.preventDefault();
  $('topup-error').textContent = '';
  const [amount, coins] = $('package').value.split('|');
  const fd = new FormData();
  fd.append('amount_paise', amount);
  fd.append('coins', coins);
  fd.append('utr', $('utr').value.trim());
  const file = $('screenshot').files[0];
  if (file) fd.append('screenshot', file);

  try {
    const d = await api('api/topup-request.php', { method: 'POST', body: fd });
    $('topup-dialog').close();
    $('topup-form').reset();
    await refreshMe();
    toast(d.message || 'Request submitted');
  } catch (err) {
    $('topup-error').textContent = err.message;
  }
});

$('join-form').addEventListener('submit', async e => {
  e.preventDefault();
  $('join-error').textContent = '';
  const tournamentId = Number($('join-tournament-id').value);
  if (!tournamentId) {
    $('join-error').textContent = 'Select a tournament first.';
    return;
  }

  const selectedTeamId = $('join-team-select').value;
  const params = new URLSearchParams({
    tournament_id: String(tournamentId),
    team_id: selectedTeamId || '',
    team_name: $('join-team-name').value.trim(),
  });

  try {
    const d = await api('api/join-tournament.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString(),
    });
    $('join-dialog').close();
    await refreshMe();
    await refreshTournaments();
    toast(d.message || 'Tournament joined');
  } catch (err) {
    $('join-error').textContent = err.message;
  }
});

$('team-create-form').addEventListener('submit', async e => {
  e.preventDefault();
  $('team-error').textContent = '';
  const payload = new URLSearchParams({
    team_name: $('team-name').value.trim(),
    game: $('team-game').value.trim(),
    description: $('team-description').value.trim(),
  });

  try {
    const d = await api('api/team-create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: payload.toString(),
    });
    $('team-create-form').reset();
    await refreshTeams();
    toast(d.message || 'Team created');
  } catch (err) {
    $('team-error').textContent = err.message;
  }
});

$('ticket-form').addEventListener('submit', async e => {
  e.preventDefault();
  $('ticket-error').textContent = '';
  const payload = new URLSearchParams({
    subject: $('ticket-subject').value.trim(),
    message: $('ticket-message').value.trim(),
  });

  try {
    const d = await api('api/ticket.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: payload.toString(),
    });
    $('ticket-form').reset();
    await refreshTickets();
    toast(d.message || 'Support request sent');
  } catch (err) {
    $('ticket-error').textContent = err.message;
  }
});

(async () => {
  await refreshMe();
  await refreshTournaments();
})();
