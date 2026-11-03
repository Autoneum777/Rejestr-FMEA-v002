<script setup>
import { computed, ref, watch } from 'vue'
import { userName } from '../format'
import { closeModal, state, submitPerson } from '../store'

const userId = ref('')
const selectedCount = computed(() => (
  state.personKind === 'fmea' ? state.selectedFmea.size : state.selectedCp.size
))

watch(() => state.modal, (modal) => {
  if (modal !== 'person') return
  userId.value = state.users[0] ? String(state.users[0].id) : ''
})

function optionLabel(user) {
  return `${userName(user)}${user.dzial ? ` · ${user.dzial}` : ''}${user.email ? ` · ${user.email}` : ''}`
}

async function onSubmit() {
  try {
    await submitPerson(userId.value)
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'person'" class="modal-backdrop" @click.self="closeModal">
    <form class="modal" @submit.prevent="onSubmit">
      <h3>{{ state.personKind === 'fmea' ? 'Osoba wiodąca FMEA' : 'Odpowiedzialny QE planu kontroli' }}</h3>
      <p class="hint">Zmiana obejmie zaznaczone pozycje: {{ selectedCount }}.</p>
      <label>Użytkownik
        <select v-model="userId" required>
          <option v-for="user in state.users" :key="user.id" :value="String(user.id)">{{ optionLabel(user) }}</option>
        </select>
      </label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</template>
