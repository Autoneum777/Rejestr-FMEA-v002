<script setup>
import { onMounted, ref } from 'vue'
import StatsBar from './StatsBar.vue'
import { useStickyHead } from '../sticky'
import { fmtDate, statusLabel } from '../format'
import {
  exportCp,
  filteredCp,
  importCp,
  loadCp,
  openCpDrawer,
  openCpForm,
  openPersonModal,
  openPurge,
  openReview,
  openSettings,
  setCpFilter,
  state,
  toggleCp,
  toggleVisibleCp,
} from '../store'

const table = ref(null)
const file = ref(null)
useStickyHead(table)

const cards = [
  { key: 'total', label: 'Aktywnych planów' },
  { key: 'przeterminowane', label: 'Przeterminowane', className: 'bad' },
  { key: 'wymaga_weryfikacji', label: 'Wymaga weryfikacji', className: 'verify' },
  { key: 'ostrzezenie', label: 'Miesiąc do końca', className: 'warn' },
  { key: 'aktualne', label: 'Aktualne', className: 'ok' },
  { key: 'nieaktywne', label: 'Serwis, poza statystykami', className: 'muted-stat' },
]

const filters = [
  { label: 'Wszystkie', status: '', aktywne: '1' },
  { label: 'Przeterminowane', status: 'przeterminowane', aktywne: '1' },
  { label: 'Wymaga weryfikacji', status: 'wymaga_weryfikacji', aktywne: '1' },
  { label: 'Miesiąc do końca', status: 'ostrzezenie', aktywne: '1' },
  { label: 'Aktualne', status: 'aktualne', aktywne: '1' },
  { label: 'Nieaktywne', status: '', aktywne: '0' },
]

onMounted(async () => {
  try {
    await loadCp()
  } catch (err) {
    state.cpError = err.message
  }
})

function onSearch(event) {
  state.cpQ = event.target.value.trim()
  loadCp().catch((err) => { state.cpError = err.message })
}

function activeFilter(filter) {
  return state.cpStatus === filter.status && state.cpAktywne === filter.aktywne
}

async function onFile(event) {
  const picked = event.target.files?.[0]
  event.target.value = ''
  if (!picked) return
  try {
    await importCp(picked)
  } catch (err) {
    alert(err.message)
  }
}

function onRowClick(event, id) {
  if (event.target.closest('button, input, a')) return
  openCpDrawer(id).catch((err) => alert(err.message))
}
</script>

<template>
  <header class="top">
    <div>
      <p class="eyebrow">Quality · Plany kontroli</p>
      <h1>Rejestr planów kontroli</h1>
    </div>
    <div class="top-actions">
      <button type="button" class="btn ghost" @click="openSettings">Kolorystyka</button>
      <button type="button" class="btn ghost" @click="file.click()">Wczytaj plik Excel</button>
      <input ref="file" type="file" accept=".xlsx,.xlsm" hidden @change="onFile" />
      <button type="button" class="btn blue" @click="exportCp">Eksport do Excela</button>
      <button type="button" class="btn ghost" @click="openPersonModal('cp')">Zmień osobę</button>
      <button type="button" class="btn" @click="openCpForm(null)">Nowy plan</button>
      <button type="button" class="btn danger" @click="openPurge('cp')">Wyczyść bazę</button>
    </div>
  </header>

  <StatsBar :summary="state.cpSummary" :cards="cards" />

  <section class="toolbar">
    <input class="search" type="search" placeholder="Szukaj: nr planu, nr FMEA, symbol, model, osoba…" :value="state.cpQ" @input="onSearch" />
    <div class="filters">
      <button
        v-for="filter in filters"
        :key="filter.label"
        type="button"
        class="chip"
        :class="{ active: activeFilter(filter) }"
        @click="setCpFilter(filter.status, filter.aktywne)"
      >{{ filter.label }}</button>
    </div>
  </section>

  <main class="table-wrap">
    <table ref="table">
      <thead>
        <tr>
          <th class="check"><input type="checkbox" title="Zaznacz widoczne" :checked="state.cpCheckAll" @change="toggleVisibleCp($event.target.checked)" /></th>
          <th>Plan kontroli nr</th>
          <th>Numer FMEA</th>
          <th>Odpowiedzialny QE</th>
          <th>Symbol wyrobu</th>
          <th>Model samochodu</th>
          <th>Nr rysunku lub części</th>
          <th>Linia MES</th>
          <th>Data rewizji</th>
          <th>Termin kolejnej rewizji</th>
          <th></th>
        </tr>
        <tr class="filters-row">
          <th></th>
          <th><input v-model="state.cpCols.nr_planu" placeholder="Filtr" /></th>
          <th><input v-model="state.cpCols.nr_fmea" placeholder="Filtr" /></th>
          <th><input v-model="state.cpCols.osoba_wiodaca" placeholder="Filtr" /></th>
          <th><input v-model="state.cpCols.symbol_wyrobu" placeholder="Filtr" /></th>
          <th><input v-model="state.cpCols.model" placeholder="Filtr" /></th>
          <th><input v-model="state.cpCols.nr_rysunku" placeholder="Filtr" /></th>
          <th><input v-model="state.cpCols.linia_mes" placeholder="Filtr" /></th>
          <th><input v-model="state.cpCols.last_review_date" placeholder="rrrr-mm-dd" /></th>
          <th><input v-model="state.cpCols.next_due_date" placeholder="rrrr-mm-dd" /></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="state.cpError"><td colspan="11">{{ state.cpError }}</td></tr>
        <tr v-else-if="!filteredCp.length"><td colspan="11" class="muted">Brak planów kontroli.</td></tr>
        <tr
          v-for="item in filteredCp"
          :key="item.id"
          class="row"
          :class="item.aktywne ? `row-${item.status}` : 'row-inactive'"
          @click="onRowClick($event, item.id)"
        >
          <td class="check"><input type="checkbox" :checked="state.selectedCp.has(item.id)" @change="toggleCp(item.id, $event.target.checked)" /></td>
          <td><strong>{{ item.nr_planu }}</strong></td>
          <td>
            <template v-if="item.nr_fmea">
              {{ item.nr_fmea }}
              <span v-if="!item.fmea_linked" class="muted">(brak w rejestrze)</span>
            </template>
            <template v-else>—</template>
          </td>
          <td>{{ item.osoba_wiodaca || '—' }}</td>
          <td class="pn">{{ item.symbol_wyrobu || '—' }}</td>
          <td>{{ item.model || '—' }}</td>
          <td>{{ item.nr_rysunku || '—' }}</td>
          <td>{{ item.linia_mes || '—' }}</td>
          <td>{{ fmtDate(item.last_review_date) }}</td>
          <td>
            {{ fmtDate(item.next_due_date) }}
            <div class="muted">{{ item.aktywne ? statusLabel(item.status) : 'Nieaktywny' }}</div>
          </td>
          <td><button type="button" class="btn small" @click="openReview(item.id, 'cp')">Aktualizuj datę</button></td>
        </tr>
      </tbody>
    </table>
  </main>
</template>
