<script setup>
import { computed, reactive, watch } from 'vue'
import { addMonths, fmtDate, today } from '../format'
import { closeModal, state, submitCp } from '../store'

const empty = () => ({
  nr_planu: '',
  nr_fmea: '',
  qe: '',
  symbol_wyrobu: '',
  model: '',
  nr_rysunku: '',
  linia_mes: '',
  data_rewizji: '',
  reason: '',
  status_przegladu: 'OK',
  nazwa_wyrobu: '',
  nr_zmiany: '',
  data_zatwierdzenia: '',
  nr_rewizji: '',
  data_anulowania: '',
  uwagi: '',
})

const form = reactive(empty())
const creating = computed(() => !state.editingCpId)
const preview = computed(() => (
  form.data_rewizji
    ? `Termin kolejnej rewizji: ${fmtDate(addMonths(form.data_rewizji, 12))}`
    : 'Termin kolejnej rewizji: —'
))

watch(() => state.modal, (modal) => {
  if (modal !== 'cp') return
  Object.assign(form, empty())
  const item = state.editingCp
  if (!item) {
    form.data_rewizji = today()
    return
  }
  form.nr_planu = item.nr_planu || ''
  form.nr_fmea = item.nr_fmea || ''
  form.qe = item.qe || item.osoba_wiodaca || ''
  form.symbol_wyrobu = item.symbol_wyrobu || ''
  form.model = item.model || ''
  form.nr_rysunku = item.nr_rysunku || ''
  form.linia_mes = item.linia_mes || ''
  form.status_przegladu = item.status_przegladu || 'OK'
  form.nazwa_wyrobu = item.nazwa_wyrobu || ''
  form.nr_zmiany = item.nr_zmiany || ''
  form.data_zatwierdzenia = (item.data_zatwierdzenia || '').slice(0, 10)
  form.nr_rewizji = item.nr_rewizji || ''
  form.data_anulowania = (item.data_anulowania || '').slice(0, 10)
  form.uwagi = item.uwagi || ''
})

async function onSubmit() {
  if (creating.value && !form.reason.trim()) {
    alert('Podaj powód rewizji.')
    return
  }
  const payload = {
    nr_planu: form.nr_planu,
    nr_fmea: form.nr_fmea,
    qe: form.qe,
    symbol_wyrobu: form.symbol_wyrobu,
    model: form.model,
    nr_rysunku: form.nr_rysunku,
    linia_mes: form.linia_mes,
    status_przegladu: form.status_przegladu,
    nazwa_wyrobu: form.nazwa_wyrobu,
    nr_zmiany: form.nr_zmiany,
    data_zatwierdzenia: form.data_zatwierdzenia,
    nr_rewizji: form.nr_rewizji,
    data_anulowania: form.data_anulowania,
    uwagi: form.uwagi,
  }
  if (creating.value) {
    payload.data_rewizji = form.data_rewizji
    payload.reason = form.reason
  }
  try {
    await submitCp(payload, creating.value)
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'cp'" class="modal-backdrop" @click.self="closeModal">
    <form class="modal modal-scroll" @submit.prevent="onSubmit">
      <h3>{{ creating ? 'Nowy plan kontroli' : `Edycja ${state.editingCp?.nr_planu || ''}` }}</h3>
      <label>Plan kontroli nr <input v-model="form.nr_planu" required /></label>
      <label>Numer FMEA <input v-model="form.nr_fmea" /></label>
      <label>Odpowiedzialny QE <input v-model="form.qe" /></label>
      <label>Symbol wyrobu <input v-model="form.symbol_wyrobu" /></label>
      <label>Model samochodu <input v-model="form.model" /></label>
      <label>Nr rysunku lub części <input v-model="form.nr_rysunku" /></label>
      <label>Linia MES <input v-model="form.linia_mes" /></label>
      <template v-if="creating">
        <label>Data rewizji <input v-model="form.data_rewizji" type="date" required /></label>
        <p class="hint">{{ preview }}</p>
        <label>Powód rewizji <input v-model="form.reason" required placeholder="Wymagane, aby zapisać" /></label>
      </template>
      <label>Status przeglądu
        <select v-model="form.status_przegladu">
          <option value="OK">OK</option>
          <option value="Weryfikacja opóźniona">Weryfikacja opóźniona</option>
          <option value="ND">ND</option>
          <option value="Serwis">Serwis</option>
        </select>
      </label>
      <label>Nazwa wyrobu <input v-model="form.nazwa_wyrobu" /></label>
      <label>Nr zmiany <input v-model="form.nr_zmiany" /></label>
      <label>Data zatwierdzenia <input v-model="form.data_zatwierdzenia" type="date" /></label>
      <label>Nr rewizji <input v-model="form.nr_rewizji" /></label>
      <label>Data anulowania <input v-model="form.data_anulowania" type="date" /></label>
      <label>Uwagi <textarea v-model="form.uwagi" rows="2"></textarea></label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</template>
