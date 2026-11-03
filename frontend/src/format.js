export function fmtDate(value) {
  if (!value) return '—'
  const [y, m, d] = String(value).slice(0, 10).split('-')
  if (!d) return value
  return `${d}.${m}.${y}`
}

export function statusLabel(status) {
  return {
    przeterminowane: 'Przeterminowane',
    wymaga_weryfikacji: 'Wymaga weryfikacji',
    ostrzezenie: 'Miesiąc do końca',
    aktualne: 'Aktualne',
    brak_terminu: 'Brak terminu',
  }[status] || status
}

export function typeLabel(type) {
  return type === 'pelny' ? 'Pełny' : 'Zdarzeniowy'
}

export function addMonths(isoDate, months) {
  const [y, m, d] = isoDate.split('-').map(Number)
  const date = new Date(y, m - 1 + months, 1)
  const last = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate()
  date.setDate(Math.min(d, last))
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${mm}-${dd}`
}

export function today() {
  return new Date().toISOString().slice(0, 10)
}

export function userName(user) {
  return `${user.imie} ${user.nazwisko}`
}

export function blank(value) {
  const text = value == null ? '' : String(value)
  return text === '' ? '—' : text
}
