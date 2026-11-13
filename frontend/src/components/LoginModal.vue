<script setup>
import { reactive } from 'vue'
import { closeModal, state, submitLogin } from '../store'

const form = reactive({ login: '', password: '' })

async function onSubmit() {
  try {
    await submitLogin({ ...form })
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'login'" class="modal-backdrop" @click.self="closeModal">
    <form class="modal" @submit.prevent="onSubmit">
      <h3>Logowanie</h3>
      <p class="hint">Bez logowania przeglądasz rejestr jako gość (tylko odczyt). Administrator: login <strong>admin</strong>.</p>
      <label>Login <input v-model="form.login" autocomplete="username" required /></label>
      <label>Hasło <input v-model="form.password" type="password" autocomplete="current-password" required /></label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn">Zaloguj</button>
      </div>
    </form>
  </div>
</template>
