<script setup>
import { reactive, watch } from 'vue'
import { closeModal, state, submitSettings } from '../store'

const form = reactive({
  color_overdue: '#f8d0cb',
  color_warning: '#fff2cc',
  color_verification: '#d4e4f7',
  warning_days: '30',
})

watch(() => state.modal, (modal) => {
  if (modal !== 'settings') return
  form.color_overdue = String(state.settings.color_overdue || '#f8d0cb').toLowerCase()
  form.color_warning = String(state.settings.color_warning || '#fff2cc').toLowerCase()
  form.color_verification = String(state.settings.color_verification || '#d4e4f7').toLowerCase()
  form.warning_days = state.settings.warning_days
})

async function onSubmit() {
  try {
    await submitSettings({ ...form })
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'settings'" class="modal-backdrop" @click.self="closeModal">
    <form class="modal" @submit.prevent="onSubmit">
      <h3>Kolorystyka statusów</h3>
      <p class="hint">Kolory wierszy na liście i w eksporcie Excela. Pole ostrzeżenia jest ukryte — posłuży później do notyfikacji.</p>
      <label>Przeterminowane
        <input v-model="form.color_overdue" type="color" />
      </label>
      <label>Miesiąc do końca
        <input v-model="form.color_warning" type="color" />
      </label>
      <label>Wymaga weryfikacji (plany kontroli po aktualizacji FMEA)
        <input v-model="form.color_verification" type="color" />
      </label>
      <label>Ile dni przed terminem oznacza „miesiąc do końca”
        <input v-model="form.warning_days" type="number" min="1" max="180" />
      </label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn">Zapisz kolory</button>
      </div>
    </form>
  </div>
</template>
