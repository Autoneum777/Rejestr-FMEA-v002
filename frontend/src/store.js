import { computed, reactive } from 'vue'
import { api, appUrl, upload } from './api'
import { fmtDate, userName } from './format'

export const state = reactive({
  view: 'fmea',
  status: '',
  aktywne: '1',
  q: '',
  cpStatus: '',
  cpAktywne: '1',
  cpQ: '',
  currentId: null,
  reviewKind: 'fmea',
  editingId: null,
  editingFmea: null,
  editingCp: null,
  editingCpId: null,
  editingUser: null,
  deletingUser: null,
  userDeleteAssignments: [],
  userDeleteReplacements: {},
  userDeleteSelected: new Set(),
  userDeleteBulkUserId: '',
  personKind: 'cp',
  users: [],
  mailReady: false,
  mailFrom: '',
  mailHost: '',
  selectedFmea: new Set(),
  selectedCp: new Set(),
  purgeKind: 'fmea',
  purgePhrase: '',
  deleteNr: '',
  fmeaItems: [],
  fmeaSummary: null,
  cpItems: [],
  cpSummary: null,
  fmeaCols: {
    nr_fmea: '',
    part_numbers: '',
    projekt: '',
    rodzaj_wyrobu: '',
    osoba_wiodaca: '',
    current_fmea_date: '',
    last_review_reason: '',
    next_due_date: '',
  },
  cpCols: {
    nr_planu: '',
    nr_fmea: '',
    osoba_wiodaca: '',
    symbol_wyrobu: '',
    model: '',
    nr_rysunku: '',
    linia_mes: '',
    last_review_date: '',
    next_due_date: '',
  },
  settings: {
    color_overdue: '#F8D0CB',
    color_warning: '#FFF2CC',
    color_verification: '#D4E4F7',
    warning_days: '30',
  },
  drawerOpen: false,
  detail: null,
  listError: '',
  cpError: '',
  usersError: '',
  modal: null,
  fmeaCheckAll: false,
  cpCheckAll: false,
  auth: {
    role: 'guest',
    login: '',
    name: '',
    userId: null,
  },
})

function matchesCols(item, cols) {
  return Object.entries(cols).every(([key, value]) => {
    const needle = String(value || '').trim().toLowerCase()
    if (!needle) return true
    const raw = String(item[key] || '').toLowerCase()
    const shown = fmtDate(item[key]).toLowerCase()
    return raw.includes(needle) || shown.includes(needle)
  })
}

export const filteredFmea = computed(() => state.fmeaItems.filter((item) => matchesCols(item, state.fmeaCols)))
export const filteredCp = computed(() => state.cpItems.filter((item) => matchesCols(item, state.cpCols)))

export function applyColors(settings) {
  state.settings = { ...state.settings, ...settings }
  const root = document.documentElement
  root.style.setProperty('--row-overdue', state.settings.color_overdue)
  root.style.setProperty('--row-warning', state.settings.color_warning)
  root.style.setProperty('--row-verification', state.settings.color_verification)
}

export function closeModal() {
  state.modal = null
}

export function closeDrawer() {
  state.drawerOpen = false
}

export function canEdit() {
  return state.auth.role !== 'guest'
}

export function isAdmin() {
  return state.auth.role === 'admin'
}

export function applyAuth(payload) {
  state.auth = {
    role: payload?.role || 'guest',
    login: payload?.login || '',
    name: payload?.name || '',
    userId: payload?.user_id ?? null,
  }
}

export async function loadAuth() {
  const data = await api('/api/auth/me')
  applyAuth(data)
}

export function openLogin() {
  state.modal = 'login'
}

export function openPassword() {
  state.modal = 'password'
}

export async function submitLogin(payload) {
  const result = await api('/api/auth/login', { method: 'POST', body: JSON.stringify(payload) })
  applyAuth(result.user)
  closeModal()
}

export async function logout() {
  await api('/api/auth/logout', { method: 'POST', body: '{}' })
  applyAuth({ role: 'guest' })
  if (state.view === 'admin') state.view = 'fmea'
}

export async function submitPasswordChange(payload) {
  await api('/api/auth/password', { method: 'PUT', body: JSON.stringify(payload) })
  closeModal()
  alert('Hasło zostało zmienione.')
}

export function showView(name) {
  if (name === 'admin' && !isAdmin()) {
    alert('Zakładka Administracja jest dostępna tylko dla administratora.')
    return
  }
  state.view = name
  closeDrawer()
}

export function pingReminders() {
  api('/api/reminders', { method: 'POST', body: '{}' }).catch(() => {})
}

export async function loadList() {
  const params = new URLSearchParams()
  if (state.q) params.set('q', state.q)
  if (state.status) params.set('status', state.status)
  if (state.aktywne) params.set('aktywne', state.aktywne)
  const data = await api(`/api/fmea?${params.toString()}`)
  state.fmeaItems = data.items
  state.fmeaSummary = data.summary
  state.listError = ''
  if (data.settings) applyColors(data.settings)
}

export async function loadCp() {
  const params = new URLSearchParams()
  if (state.cpQ) params.set('q', state.cpQ)
  if (state.cpStatus) params.set('status', state.cpStatus)
  if (state.cpAktywne) params.set('aktywne', state.cpAktywne)
  const data = await api(`/api/cp?${params.toString()}`)
  state.cpItems = data.items
  state.cpSummary = data.summary
  state.cpError = ''
  if (data.settings) applyColors(data.settings)
}

export async function loadUsers() {
  const data = await api('/api/users')
  state.users = data.items
  state.mailReady = Boolean(data.mail?.ready)
  state.mailFrom = data.mail?.from || ''
  state.mailHost = data.mail?.host || ''
  state.usersError = ''
}

export function setFmeaFilter(status, aktywne) {
  state.status = status
  state.aktywne = aktywne
  loadList().catch((err) => { state.listError = err.message })
}

export function setCpFilter(status, aktywne) {
  state.cpStatus = status
  state.cpAktywne = aktywne
  loadCp().catch((err) => { state.cpError = err.message })
}

export async function openFmeaDrawer(id) {
  const data = await api(`/api/fmea/${id}`)
  state.currentId = id
  state.reviewKind = 'fmea'
  state.detail = { kind: 'fmea', ...data }
  state.drawerOpen = true
}

export async function openCpDrawer(id) {
  const data = await api(`/api/cp/${id}`)
  state.currentId = id
  state.reviewKind = 'cp'
  state.detail = { kind: 'cp', ...data }
  state.drawerOpen = true
}

export function openReview(id, kind) {
  state.currentId = id
  state.reviewKind = kind || 'fmea'
  state.modal = 'review'
}

function notifySummary(items) {
  if (!items?.length) return
  const lines = items.map((item) => {
    if (item.status === 'wyslane') return `${item.name}: wysłano na ${item.email}`
    if (item.status === 'brak_adresu') return `${item.name}: brak użytkownika z tym imieniem i nazwiskiem`
    if (item.status === 'brak_email') return `${item.name}: brak adresu e-mail`
    return `${item.name}: zapisano do wysłania (${item.error || 'oczekuje na serwer poczty'})`
  })
  alert(`Powiadomienia o aktualizacji FMEA:\n${lines.join('\n')}`)
}

export async function submitReview(payload) {
  const kind = state.reviewKind
  const data = await api(`/${kind === 'cp' ? 'api/cp' : 'api/fmea'}/${state.currentId}/reviews`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
  closeModal()
  if (kind === 'fmea' && data.notifications) notifySummary(data.notifications)
  if (kind === 'cp') {
    await loadCp()
    if (state.drawerOpen) await openCpDrawer(state.currentId)
  } else {
    await loadList()
    if (state.drawerOpen) await openFmeaDrawer(state.currentId)
  }
}

export async function openFmeaForm(item) {
  if (!state.users.length) {
    try {
      await loadUsers()
    } catch (err) {
      alert(err.message)
      return
    }
  }
  state.editingId = item ? item.id : null
  state.editingFmea = item ? { ...item } : null
  state.modal = 'fmea'
}

export async function submitFmea(payload, creating) {
  if (creating) {
    await api('/api/fmea', { method: 'POST', body: JSON.stringify(payload) })
  } else {
    await api(`/api/fmea/${state.editingId}`, { method: 'PUT', body: JSON.stringify(payload) })
  }
  const edited = state.editingId
  closeModal()
  await loadList()
  if (edited && state.drawerOpen) await openFmeaDrawer(edited)
}

export async function toggleFmeaActive() {
  const item = state.detail?.item
  if (!item) return
  await api(`/api/fmea/${item.id}/aktywne`, {
    method: 'PUT',
    body: JSON.stringify({ aktywne: item.aktywne ? 0 : 1 }),
  })
  await loadList()
  await openFmeaDrawer(item.id)
}

export function openDeleteModal(item) {
  const nr = String(item.nr_fmea)
  if (!confirm(`Usunąć FMEA ${nr} wraz z całą historią przeglądów?\n\nTo działanie jest bezpowrotne.`)) return
  state.currentId = item.id
  state.deleteNr = nr
  state.modal = 'delete'
}

export async function submitDelete(typed) {
  await api(`/api/fmea/${state.currentId}`, {
    method: 'DELETE',
    body: JSON.stringify({ confirm: typed }),
  })
  closeModal()
  closeDrawer()
  await loadList()
}

export function openPurge(kind) {
  const phrase = kind === 'fmea' ? 'WYCZYSC FMEA' : 'WYCZYSC PLANY'
  const label = kind === 'fmea' ? 'rejestr FMEA' : 'rejestr planów kontroli'
  if (!confirm(`Wyczyścić ${label}?\n\nZnikną wszystkie pozycje i ich historia. Użytkownicy zostaną. Tego nie da się cofnąć.`)) return
  state.purgeKind = kind
  state.purgePhrase = phrase
  state.modal = 'purge'
}

export async function submitPurge(typed) {
  await api(state.purgeKind === 'fmea' ? '/api/fmea/wyczysc' : '/api/cp/wyczysc', {
    method: 'POST',
    body: JSON.stringify({ confirm: typed }),
  })
  closeModal()
  closeDrawer()
  if (state.purgeKind === 'fmea') {
    state.selectedFmea.clear()
    state.fmeaCheckAll = false
    await loadList()
  } else {
    state.selectedCp.clear()
    state.cpCheckAll = false
    await loadCp()
  }
}

export function openSettings() {
  state.modal = 'settings'
}

export async function submitSettings(payload) {
  const saved = await api('/api/settings', { method: 'PUT', body: JSON.stringify(payload) })
  applyColors(saved)
  closeModal()
  if (state.view === 'cp') await loadCp()
  else await loadList()
}

export function exportFmea() {
  const params = new URLSearchParams()
  if (state.q) params.set('q', state.q)
  if (state.status) params.set('status', state.status)
  if (state.aktywne) params.set('aktywne', state.aktywne)
  window.location.href = appUrl(`/api/export?${params.toString()}`)
}

export function exportCp() {
  const params = new URLSearchParams()
  if (state.cpQ) params.set('q', state.cpQ)
  if (state.cpStatus) params.set('status', state.cpStatus)
  if (state.cpAktywne) params.set('aktywne', state.cpAktywne)
  window.location.href = appUrl(`/api/cp/export?${params.toString()}`)
}

const importFormatLabel = (format) => (
  format === 'app' ? 'struktura z eksportu aplikacji' : 'format oryginalny (legacy)'
)

export async function importFmea(file, format = 'legacy') {
  const kind = importFormatLabel(format)
  if (!confirm(`Nadpisać rejestr FMEA (${kind}) plikiem „${file.name}”?`)) return
  const result = await upload('/api/import', file, { format })
  const fmt = result.format === 'app' ? 'eksport aplikacji' : 'oryginalny'
  alert(`Zaimportowano ${result.fmea} FMEA i ${result.reviews} przeglądów (${fmt}). Nowi użytkownicy: ${result.users || 0}.`)
  closeDrawer()
  await loadList()
}

export async function importCp(file, format = 'legacy') {
  const kind = importFormatLabel(format)
  if (!confirm(`Nadpisać rejestr planów kontroli (${kind}) plikiem „${file.name}”?`)) return
  const result = await upload('/api/cp/import', file, { format })
  const fmt = result.format === 'app' ? 'eksport aplikacji' : 'oryginalny'
  alert(`Zaimportowano ${result.plans} planów (${fmt}), powiązanych z FMEA: ${result.linked}, nieaktywnych: ${result.inactive}. Nowi użytkownicy: ${result.users || 0}.`)
  closeDrawer()
  await loadCp()
}

export function toggleFmea(id, checked) {
  if (checked) state.selectedFmea.add(id)
  else state.selectedFmea.delete(id)
}

export function toggleCp(id, checked) {
  if (checked) state.selectedCp.add(id)
  else state.selectedCp.delete(id)
}

export function toggleVisibleFmea(checked) {
  state.fmeaCheckAll = checked
  for (const item of filteredFmea.value) toggleFmea(item.id, checked)
}

export function toggleVisibleCp(checked) {
  state.cpCheckAll = checked
  for (const item of filteredCp.value) toggleCp(item.id, checked)
}

export async function openCpForm(item) {
  if (!state.users.length) {
    try {
      await loadUsers()
    } catch (err) {
      alert(err.message)
      return
    }
  }
  state.editingCpId = item ? item.id : null
  state.editingCp = item ? { ...item } : null
  state.modal = 'cp'
}

export async function submitCp(payload, creating) {
  if (creating) {
    await api('/api/cp', { method: 'POST', body: JSON.stringify(payload) })
  } else {
    await api(`/api/cp/${state.editingCpId}`, { method: 'PUT', body: JSON.stringify(payload) })
  }
  const edited = state.editingCpId
  closeModal()
  await loadCp()
  if (edited && state.drawerOpen) await openCpDrawer(edited)
}

export async function confirmCpUnchanged() {
  const id = state.currentId
  if (!confirm('Potwierdzasz, że plan kontroli nie wymaga aktualizacji po zmianie powiązanego FMEA?')) return
  await api(`/api/cp/${id}/potwierdz-bez-zmian`, { method: 'POST', body: '{}' })
  await openCpDrawer(id)
  await loadCp()
}

export function openUserForm(user) {
  if (!isAdmin()) {
    alert('Tylko administrator może zarządzać użytkownikami.')
    return
  }
  state.editingUser = user ? { ...user } : null
  state.modal = 'user'
}

export async function submitUser(payload) {
  if (state.editingUser) {
    await api(`/api/users/${state.editingUser.id}`, { method: 'PUT', body: JSON.stringify(payload) })
  } else {
    await api('/api/users', { method: 'POST', body: JSON.stringify(payload) })
  }
  closeModal()
  await loadUsers()
}

export function assignmentKey(row) {
  return `${row.kind}:${row.id}`
}

export async function openDeleteUser(user) {
  try {
    if (!state.users.length) await loadUsers()
    const data = await api(`/api/users/${user.id}/assignments`)
    state.deletingUser = user
    state.userDeleteAssignments = data.items
    state.userDeleteReplacements = {}
    for (const row of data.items) {
      state.userDeleteReplacements[assignmentKey(row)] = ''
    }
    state.userDeleteSelected = new Set()
    state.userDeleteBulkUserId = state.users.find((u) => u.id !== user.id)?.id
      ? String(state.users.find((u) => u.id !== user.id).id)
      : ''
    state.modal = 'deleteUser'
  } catch (err) {
    alert(err.message)
  }
}

export function toggleUserDeleteRow(key, checked) {
  if (checked) state.userDeleteSelected.add(key)
  else state.userDeleteSelected.delete(key)
}

export function applyBulkUserDeleteReplacement() {
  const replacement = state.userDeleteBulkUserId
  if (!replacement) {
    alert('Wybierz użytkownika do grupowego zastąpienia.')
    return
  }
  if (!state.userDeleteSelected.size) {
    alert('Zaznacz pozycje, którym ustawisz to samo zastępstwo.')
    return
  }
  for (const key of state.userDeleteSelected) {
    state.userDeleteReplacements[key] = replacement
  }
}

export async function submitDeleteUser() {
  const user = state.deletingUser
  if (!user) return
  const replacements = state.userDeleteAssignments.map((row) => {
    const key = assignmentKey(row)
    return {
      kind: row.kind,
      id: row.id,
      user_id: Number(state.userDeleteReplacements[key] || 0),
    }
  })
  if (replacements.some((row) => !row.user_id)) {
    alert('Wybierz zastępcę dla każdej przypisanej pozycji.')
    return
  }
  if (replacements.some((row) => row.user_id === user.id)) {
    alert('Zastępca nie może być usuwanym użytkownikiem.')
    return
  }
  const label = replacements.length
    ? `Usunąć ${userName(user)} po przepisaniu ${replacements.length} pozycji?`
    : `Usunąć użytkownika ${userName(user)}?`
  if (!confirm(label)) return
  await api(`/api/users/${user.id}`, {
    method: 'DELETE',
    body: JSON.stringify({ replacements }),
  })
  closeModal()
  state.deletingUser = null
  state.userDeleteAssignments = []
  state.userDeleteReplacements = {}
  state.userDeleteSelected.clear()
  await loadUsers()
  if (state.view === 'fmea') await loadList()
  else if (state.view === 'cp') await loadCp()
}

export async function openPersonModal(kind) {
  const selected = kind === 'fmea' ? state.selectedFmea : state.selectedCp
  if (!selected.size) {
    alert('Zaznacz pozycje, którym chcesz zmienić osobę odpowiedzialną.')
    return
  }
  try {
    if (!state.users.length) {
      const data = await api('/api/users')
      state.users = data.items
    }
  } catch (err) {
    alert(err.message)
    return
  }
  if (!state.users.length) {
    alert('Najpierw dodaj użytkowników na zakładce Użytkownicy.')
    return
  }
  state.personKind = kind
  state.modal = 'person'
}

export async function submitPerson(userId) {
  const selected = state.personKind === 'fmea' ? state.selectedFmea : state.selectedCp
  const result = await api(state.personKind === 'fmea' ? '/api/fmea/osoba' : '/api/cp/osoba', {
    method: 'POST',
    body: JSON.stringify({ ids: [...selected], user_id: Number(userId) }),
  })
  closeModal()
  selected.clear()
  if (state.personKind === 'fmea') {
    state.fmeaCheckAll = false
    await loadList()
  } else {
    state.cpCheckAll = false
    await loadCp()
  }
  alert(`Zmieniono osobę odpowiedzialną na ${result.name} w ${result.updated} pozycjach.`)
}
