<script setup>
import { reactive } from 'vue'
import { closeModal, state, submitPasswordChange } from '../store'

const form = reactive({ current: '', password: '', repeat: '' })

async function onSubmit() {
  if (form.password !== form.repeat) {
    alert('Powtórzone hasło musi być takie samo.')
    return
  }
  try {
    await submitPasswordChange({ current: form.current, password: form.password })
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'password'" class="modal-backdrop" @click.self="closeModal">
    <form class="modal" @submit.prevent="onSubmit">
      <h3>Zmiana hasła</h3>
      <label>Aktualne hasło <input v-model="form.current" type="password" autocomplete="current-password" required /></label>
      <label>Nowe hasło <input v-model="form.password" type="password" autocomplete="new-password" minlength="6" required /></label>
      <label>Powtórz nowe hasło <input v-model="form.repeat" type="password" autocomplete="new-password" minlength="6" required /></label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn">Zapisz hasło</button>
      </div>
    </form>
  </div>
</template>
