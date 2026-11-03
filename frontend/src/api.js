export function appBase() {
  const configured = import.meta.env.BASE_URL.replace(/\/$/, '')
  if (configured && configured !== '.' && configured !== '/') return configured
  const path = location.pathname
  if (path === '/fmeareg' || path.startsWith('/fmeareg/')) return '/fmeareg'
  return ''
}

export function appUrl(url) {
  const path = url.startsWith('/') ? url : `/${url}`
  return `${appBase()}${path}`
}

export async function api(url, options = {}) {
  const res = await fetch(appUrl(url), {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
  })
  let data
  try {
    data = await res.json()
  } catch {
    throw new Error(res.ok ? 'Pusta odpowiedź serwera' : 'Serwer API nie odpowiada')
  }
  if (!res.ok) throw new Error(data.error || 'Błąd zapisu')
  return data
}

export async function upload(url, file) {
  const body = new FormData()
  body.append('file', file)
  const res = await fetch(appUrl(url), { method: 'POST', body })
  const raw = await res.text()
  let result
  try {
    result = JSON.parse(raw)
  } catch {
    throw new Error('Import nie powiódł się. Serwer zwrócił błąd zamiast danych.')
  }
  if (!res.ok) throw new Error(result.error || 'Błąd importu')
  return result
}
