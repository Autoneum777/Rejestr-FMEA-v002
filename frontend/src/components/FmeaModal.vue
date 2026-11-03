<script setup>
import { computed, reactive, watch } from 'vue'
import { addMonths, fmtDate, today } from '../format'
import { closeModal, state, submitFmea } from '../store'

const form = reactive({
  nr_fmea: '',
  part_numbers: '',
  projekt: '',
  rodzaj_wyrobu: '',
  osoba_wiodaca: '',
  uwagi: '',
  fmea_date: '',
  reason: '',
})

const creating = computed(() => !state.editingId)
const preview = computed(() => (
  form.fmea_date
    ? `Termin kolejnego przeglądu: ${fmtDate(addMonths(form.fmea_date, 12))}`
    : 'Termin kolejnego przeglądu: —'
))

watch(() => state.modal, (modal) => {
  if (modal !== 'fmea') return
  const item = state.editingFmea
  form.nr_fmea = item?.nr_fmea || ''
  form.part_numbers = item?.part_numbers || ''
  form.projekt = item?.projekt || ''
  form.rodzaj_wyrobu = item?.rodzaj_wyrobu || ''
  form.osoba_wiodaca = item?.osoba_wiodaca || ''
  form.uwagi = item?.uwagi || ''
  form.reason = ''
  form.fmea_date = item ? '' : today()
})

async function onSubmit() {
  const payload = {
    nr_fmea: form.nr_fmea,
    part_numbers: form.part_numbers,
    projekt: form.projekt,
    rodzaj_wyrobu: form.rodzaj_wyrobu,
    osoba_wiodaca: form.osoba_wiodaca,
    uwagi: form.uwagi,
  }
  if (creating.value) {
    payload.fmea_date = form.fmea_date
    payload.reason = form.reason
  }
  try {
    await submitFmea(payload, creating.value)
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'fmea'" class="modal-backdrop" @click.self="closeModal">
    <form class="modal" @submit.prevent="onSubmit">
      <h3>{{ creating ? 'Nowe FMEA' : `Edycja FMEA ${state.editingFmea?.nr_fmea || ''}` }}</h3>
      <label>Nr FMEA <input v-model="form.nr_fmea" required /></label>
      <label>Wyroby gotowe (PN) <textarea v-model="form.part_numbers" rows="2"></textarea></label>
      <label>Projekt <input v-model="form.projekt" /></label>
      <label>Rodzaj wyrobu <input v-model="form.rodzaj_wyrobu" /></label>
      <label>Osoba wiodąca <input v-model="form.osoba_wiodaca" /></label>
      <template v-if="creating">
        <label>Data FMEA
          <input v-model="form.fmea_date" type="date" required />
        </label>
        <p class="hint">{{ preview }}</p>
        <label>Powód (opcjonalnie)
          <input v-model="form.reason" type="text" placeholder="np. Wydanie FMEA, wdrożenie projektu…" />
        </label>
      </template>
      <label>Uwagi <textarea v-model="form.uwagi" rows="2"></textarea></label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</template>
