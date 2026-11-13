<script setup>
import { computed } from 'vue'
import { blank, fmtDate, statusLabel, typeLabel } from '../format'
import {
  closeDrawer,
  confirmCpUnchanged,
  openCpForm,
  openDeleteModal,
  openFmeaForm,
  openReview,
  state,
  toggleFmeaActive,
  canEdit,
} from '../store'

const item = computed(() => state.detail?.item || null)
const isFmea = computed(() => state.detail?.kind === 'fmea')

const title = computed(() => {
  if (!item.value) return ''
  if (isFmea.value) return item.value.projekt || item.value.rodzaj_wyrobu || 'Szczegóły'
  return item.value.nazwa_wyrobu || item.value.symbol_wyrobu || 'Plan kontroli'
})

const eyebrow = computed(() => {
  if (!item.value) return ''
  return isFmea.value ? `FMEA ${item.value.nr_fmea}` : item.value.nr_planu
})

function fmeaProject(row) {
  if (!row.nr_fmea) return '—'
  return row.fmea_linked ? `${row.nr_fmea} · ${row.fmea_projekt || ''}` : row.nr_fmea
}

async function run(action) {
  try {
    await action()
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.drawerOpen" class="drawer-backdrop" @click="closeDrawer"></div>
  <aside v-if="state.drawerOpen && item" class="drawer">
    <div class="drawer-head">
      <div>
        <p class="eyebrow">{{ eyebrow }}</p>
        <h2>{{ title }}</h2>
      </div>
      <button type="button" class="icon-btn" aria-label="Zamknij" @click="closeDrawer">×</button>
    </div>

    <div v-if="isFmea" class="kv">
      <div class="muted">Wyroby gotowe</div><div>{{ blank(item.part_numbers) }}</div>
      <div class="muted">Projekt</div><div>{{ blank(item.projekt) }}</div>
      <div class="muted">Rodzaj wyrobu</div><div>{{ blank(item.rodzaj_wyrobu) }}</div>
      <div class="muted">Osoba wiodąca</div><div>{{ blank(item.osoba_wiodaca) }}</div>
      <div class="muted">Data obecnego FMEA</div><div>{{ fmtDate(item.current_fmea_date) }}</div>
      <div class="muted">Ostatni pełny FMEA</div><div>{{ fmtDate(item.last_full_date) }}</div>
      <div class="muted">Termin kolejnej rewizji</div><div>{{ fmtDate(item.next_due_date) }}</div>
      <div class="muted">Dni od pełnego FMEA</div><div>{{ item.days_since_full ?? '—' }}</div>
      <div class="muted">Status</div>
      <div><span class="pill" :class="item.aktywne ? item.status : 'brak_terminu'">{{ item.aktywne ? statusLabel(item.status) : 'Nieaktywne' }}</span></div>
      <div class="muted">Uwagi</div><div>{{ blank(item.uwagi) }}</div>
    </div>

    <div v-else class="kv">
      <div class="muted">Plan kontroli</div><div>{{ item.nr_planu }}</div>
      <div class="muted">Numer FMEA</div><div>{{ fmeaProject(item) }}</div>
      <div class="muted">Osoba wiodąca</div><div>{{ blank(item.osoba_wiodaca) }}</div>
      <div class="muted">Symbol wyrobu</div><div>{{ blank(item.symbol_wyrobu) }}</div>
      <div class="muted">Model samochodu</div><div>{{ blank(item.model) }}</div>
      <div class="muted">Nazwa wyrobu</div><div>{{ blank(item.nazwa_wyrobu) }}</div>
      <div class="muted">Data rewizji</div><div>{{ fmtDate(item.last_review_date) }}</div>
      <div class="muted">Termin kolejnej rewizji</div><div>{{ fmtDate(item.next_due_date) }}</div>
      <div class="muted">Status</div>
      <div><span class="pill" :class="item.aktywne ? item.status : 'brak_terminu'">{{ item.aktywne ? statusLabel(item.status) : 'Nieaktywny' }}</span></div>
      <div class="muted">Status z pliku</div><div>{{ blank(item.status_przegladu) }}</div>
      <div class="muted">Nr zmiany</div><div>{{ blank(item.nr_zmiany) }}</div>
      <div class="muted">Data zatwierdzenia</div><div>{{ fmtDate(item.data_zatwierdzenia) }}</div>
      <div class="muted">Nr rewizji</div><div>{{ blank(item.nr_rewizji) }}</div>
      <div class="muted">Uwagi</div><div>{{ blank(item.uwagi) }}</div>
    </div>

    <section v-if="isFmea" class="history">
      <h3>Powiązane plany kontroli ({{ (state.detail.control_plans || []).length }})</h3>
      <table>
        <thead><tr><th>Nr planu</th><th>Symbol</th><th>Termin rewizji</th></tr></thead>
        <tbody>
          <tr v-if="!(state.detail.control_plans || []).length"><td colspan="3" class="muted">Brak powiązanych planów.</td></tr>
          <tr v-for="plan in state.detail.control_plans || []" :key="plan.id || plan.nr_planu">
            <td>{{ plan.nr_planu }}</td>
            <td>{{ plan.symbol_wyrobu || '—' }}</td>
            <td>{{ fmtDate(plan.next_due_date) }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <div v-if="canEdit()" class="drawer-actions">
      <button v-if="!isFmea && item.wymaga_weryfikacji" type="button" class="btn" @click="run(confirmCpUnchanged)">Potwierdź: dokument nie wymaga aktualizacji</button>
      <button type="button" class="btn" @click="openReview(item.id, isFmea ? 'fmea' : 'cp')">{{ isFmea ? 'Aktualizuj datę FMEA' : 'Aktualizuj datę' }}</button>
      <button v-if="isFmea" type="button" class="btn ghost" @click="openFmeaForm(item)">Edytuj dane</button>
      <button v-else type="button" class="btn ghost" @click="openCpForm(item)">Edytuj dane</button>
      <button v-if="isFmea" type="button" class="btn ghost" @click="run(toggleFmeaActive)">{{ item.aktywne ? 'Dezaktywuj projekt' : 'Aktywuj projekt' }}</button>
      <button v-if="isFmea" type="button" class="btn danger" @click="openDeleteModal(item)">Usuń bezpowrotnie</button>
    </div>

    <section class="history">
      <h3>{{ isFmea ? 'Historia przeglądów' : 'Historia rewizji' }}</h3>
      <table>
        <thead>
          <tr>
            <th>Data</th>
            <th>Typ</th>
            <th>Powód</th>
            <th>Termin po zapisie</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!(state.detail.reviews || []).length"><td colspan="4" class="muted">Brak historii.</td></tr>
          <tr v-for="review in state.detail.reviews || []" :key="review.id || review.review_date + review.reason">
            <td>{{ fmtDate(review.review_date) }}</td>
            <td :class="`type-${review.review_type}`">{{ typeLabel(review.review_type) }}</td>
            <td>{{ review.reason || '—' }}</td>
            <td>{{ fmtDate(review.next_due_after) }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section v-if="!isFmea" class="history">
      <h3>Data anulowania</h3>
      <p>{{ fmtDate(item.data_anulowania) }}</p>
    </section>

    <section class="history">
      <h3>Historia zmian</h3>
      <table>
        <thead><tr><th>Kiedy</th><th>Pole</th><th>Było</th><th>Jest</th></tr></thead>
        <tbody>
          <tr v-if="!(state.detail.changes || []).length"><td colspan="4" class="muted">Brak zmian opisów i statusów.</td></tr>
          <tr v-for="(change, index) in state.detail.changes || []" :key="index">
            <td>{{ fmtDate(change.created_at) }}</td>
            <td>{{ change.field_label }}</td>
            <td>{{ change.old_value || '—' }}</td>
            <td>{{ change.new_value || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </aside>
</template>
