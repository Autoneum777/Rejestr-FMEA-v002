<script setup>
import { nextTick, ref, watch } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, required: true },
  hint: { type: String, default: '' },
  phrase: { type: String, default: '' },
  label: { type: String, default: 'Wpisz frazę potwierdzenia' },
  confirmLabel: { type: String, default: 'Potwierdź' },
})
const emit = defineEmits(['cancel', 'submit'])
const typed = ref('')
const input = ref(null)

watch(() => props.open, async (open) => {
  if (!open) return
  typed.value = ''
  await nextTick()
  input.value?.focus()
})

function onSubmit() {
  const value = typed.value.trim()
  if (value !== props.phrase) {
    alert(props.label.startsWith('Wpisz numer') ? `Wpisz dokładnie numer FMEA: ${props.phrase}` : `Wpisz dokładnie: ${props.phrase}`)
    return
  }
  emit('submit', value)
}
</script>

<template>
  <div v-if="open" class="modal-backdrop" @click.self="emit('cancel')">
    <form class="modal" @submit.prevent="onSubmit">
      <h3>{{ title }}</h3>
      <p class="hint">{{ hint }}</p>
      <label>{{ label }}
        <input ref="input" v-model="typed" autocomplete="off" required />
      </label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="emit('cancel')">Anuluj</button>
        <button type="submit" class="btn danger">{{ confirmLabel }}</button>
      </div>
    </form>
  </div>
</template>
