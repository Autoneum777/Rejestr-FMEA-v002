<script setup>
import { onMounted, ref } from 'vue'
import StatsBar from './StatsBar.vue'
import { useStickyHead } from '../sticky'
import { fmtDate, statusLabel } from '../format'
import {
  exportFmea,
  filteredFmea,
  importFmea,
  loadList,
  openFmeaDrawer,
  openFmeaForm,
  openPersonModal,
  openPurge,
  openReview,
  openSettings,
  setFmeaFilter,
  state,
  toggleFmea,
  toggleVisibleFmea,
} from '../store'

const table = ref(null)
const file = ref(null)
useStickyHead(table)

const cards = [
  { key: 'total', label: 'Pozycji na liście' },
  { key: 'przeterminowane', label: 'Przeterminowane', className: 'bad' },
  { key: 'ostrzezenie', label: 'Miesiąc do końca', className: 'warn' },
  { key: 'aktualne', label: 'Aktualne', className: 'ok' },
  { key: 'nieaktywne', label: 'Nieaktywne (poza statystykami)', className: 'muted-stat' },
]

const filters = [
  { label: 'Wszystkie', status: '', aktywne: '1' },
  { label: 'Przeterminowane', status: 'przeterminowane', aktywne: '1' },
  { label: 'Miesiąc do końca', status: 'ostrzezenie', aktywne: '1' },
  { label: 'Aktualne', status: 'aktualne', aktywne: '1' },
  { label: 'Projekty nieaktywne', status: '', aktywne: '0' },
]

onMounted(async () => {
  try {
    await loadList()
  } catch (err) {
    state.listError = err.message
  }
})

function onSearch(event) {
  state.q = event.target.value.trim()
  loadList().catch((err) => { state.listError = err.message })
}

function activeFilter(filter) {
  return state.status === filter.status && state.aktywne === filter.aktywne
}

async function onFile(event) {
  const picked = event.target.files?.[0]
  event.target.value = ''
  if (!picked) return
  try {
    await importFmea(picked)
  } catch (err) {
    alert(err.message)
  }
}

function onRowClick(event, id) {
  if (event.target.closest('button, input, a')) return
  openFmeaDrawer(id).catch((err) => alert(err.message))
}
</script>

<template>
  <header class="top">
    <div>
      <p class="eyebrow">Quality · FMEA</p>
      <h1>Rejestr FMEA</h1>
    </div>
    <div class="top-actions">
      <button type="button" class="btn ghost" @click="openSettings">Kolorystyka</button>
      <button type="button" class="btn ghost" @click="file.click()">Wczytaj plik Excel</button>
      <input ref="file" type="file" accept=".xlsx,.xlsm" hidden @change="onFile" />
      <button type="button" class="btn blue" @click="exportFmea">Eksport do Excela</button>
      <button type="button" class="btn ghost" @click="openPersonModal('fmea')">Zmień osobę</button>
      <button type="button" class="btn" @click="openFmeaForm(null)">Nowe FMEA</button>
      <button type="button" class="btn danger" @click="openPurge('fmea')">Wyczyść bazę</button>
    </div>
  </header>

  <StatsBar :summary="state.fmeaSummary" :cards="cards" />

  <section class="toolbar">
    <input class="search" type="search" placeholder="Szukaj: nr FMEA, PN, projekt, osoba…" :value="state.q" @input="onSearch" />
    <div class="filters">
      <button
        v-for="filter in filters"
        :key="filter.label"
        type="button"
        class="chip"
        :class="{ active: activeFilter(filter) }"
        @click="setFmeaFilter(filter.status, filter.aktywne)"
      >{{ filter.label }}</button>
    </div>
  </section>

  <main class="table-wrap">
    <table ref="table">
      <thead>
        <tr>
          <th class="check"><input type="checkbox" title="Zaznacz widoczne" :checked="state.fmeaCheckAll" @change="toggleVisibleFmea($event.target.checked)" /></th>
          <th>Nr FMEA</th>
          <th>Wyroby gotowe (PN)</th>
          <th>Projekt</th>
          <th>Rodzaj wyrobu</th>
          <th>Osoba wiodąca</th>
          <th>Data obecnego FMEA</th>
          <th>Powód ost. rewizji</th>
          <th>Termin kolejnej rewizji</th>
          <th></th>
        </tr>
        <tr class="filters-row">
          <th></th>
          <th><input v-model="state.fmeaCols.nr_fmea" placeholder="Filtr" /></th>
          <th><input v-model="state.fmeaCols.part_numbers" placeholder="Filtr" /></th>
          <th><input v-model="state.fmeaCols.projekt" placeholder="Filtr" /></th>
          <th><input v-model="state.fmeaCols.rodzaj_wyrobu" placeholder="Filtr" /></th>
          <th><input v-model="state.fmeaCols.osoba_wiodaca" placeholder="Filtr" /></th>
          <th><input v-model="state.fmeaCols.current_fmea_date" placeholder="rrrr-mm-dd" /></th>
          <th><input v-model="state.fmeaCols.last_review_reason" placeholder="Filtr" /></th>
          <th><input v-model="state.fmeaCols.next_due_date" placeholder="rrrr-mm-dd" /></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="state.listError"><td colspan="10">{{ state.listError }}</td></tr>
        <tr v-else-if="!filteredFmea.length"><td colspan="10" class="muted">Brak rekordów.</td></tr>
        <tr
          v-for="item in filteredFmea"
          :key="item.id"
          class="row"
          :class="item.aktywne ? `row-${item.status}` : 'row-inactive'"
          @click="onRowClick($event, item.id)"
        >
          <td class="check"><input type="checkbox" :checked="state.selectedFmea.has(item.id)" @change="toggleFmea(item.id, $event.target.checked)" /></td>
          <td><strong>{{ item.nr_fmea }}</strong></td>
          <td class="pn">{{ item.part_numbers || '—' }}</td>
          <td>{{ item.projekt || '—' }}</td>
          <td>{{ item.rodzaj_wyrobu || '—' }}</td>
          <td>{{ item.osoba_wiodaca || '—' }}</td>
          <td>{{ fmtDate(item.current_fmea_date) }}</td>
          <td>{{ item.last_review_reason || '—' }}</td>
          <td>
            {{ fmtDate(item.next_due_date) }}
            <div class="muted">{{ item.aktywne ? statusLabel(item.status) : 'Nieaktywne' }}</div>
          </td>
          <td><button type="button" class="btn small" @click="openReview(item.id, 'fmea')">Aktualizuj datę</button></td>
        </tr>
      </tbody>
    </table>
  </main>
</template>
