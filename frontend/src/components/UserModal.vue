<script setup>
import { reactive, watch } from 'vue'
import { closeModal, state, submitUser } from '../store'

const form = reactive({ imie: '', nazwisko: '', dzial: '', email: '' })

watch(() => state.modal, (modal) => {
  if (modal !== 'user') return
  const user = state.editingUser
  form.imie = user?.imie || ''
  form.nazwisko = user?.nazwisko || ''
  form.dzial = user?.dzial || ''
  form.email = user?.email || ''
})

async function onSubmit() {
  try {
    await submitUser({ ...form })
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'user'" class="modal-backdrop" @click.self="closeModal">
    <form class="modal" @submit.prevent="onSubmit">
      <h3>{{ state.editingUser ? 'Edycja użytkownika' : 'Nowy użytkownik' }}</h3>
      <label>Imię <input v-model="form.imie" required /></label>
      <label>Nazwisko <input v-model="form.nazwisko" required /></label>
      <label>Dział
        <select v-model="form.dzial" required>
          <option value="">Wybierz</option>
          <option value="QD">QD</option>
          <option value="PED">PED</option>
        </select>
      </label>
      <label>E-mail <input v-model="form.email" type="email" placeholder="uzupełnisz później" /></label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</template>
