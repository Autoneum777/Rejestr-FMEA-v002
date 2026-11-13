<script setup>
import { computed, reactive, watch } from 'vue'
import { closeModal, state, submitUser } from '../store'

const form = reactive({ imie: '', nazwisko: '', dzial: '', email: '', login: '', password: '', isAdmin: false })

const lockedAdmin = computed(() => (
  state.editingUser?.login === 'admin' && state.editingUser?.role === 'admin'
))

watch(() => state.modal, (modal) => {
  if (modal !== 'user') return
  const user = state.editingUser
  form.imie = user?.imie || ''
  form.nazwisko = user?.nazwisko || ''
  form.dzial = user?.dzial || ''
  form.email = user?.email || ''
  form.login = user?.login || ''
  form.password = ''
  form.isAdmin = user?.role === 'admin'
})

async function onSubmit() {
  try {
    await submitUser({
      imie: form.imie,
      nazwisko: form.nazwisko,
      dzial: form.dzial,
      email: form.email,
      login: form.login,
      password: form.password,
      role: form.isAdmin ? 'admin' : 'user',
    })
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'user'" class="modal-backdrop" @click.self="closeModal">
    <form class="modal" @submit.prevent="onSubmit">
      <h3>{{ state.editingUser ? 'Edycja użytkownika' : 'Nowy użytkownik' }}</h3>
      <label>Login <input v-model="form.login" :required="!state.editingUser" placeholder="Puste = wygenerowany z imienia i nazwiska" /></label>
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
      <label class="check-label">
        <input v-model="form.isAdmin" type="checkbox" :disabled="lockedAdmin" />
        Uprawnienia administratora
      </label>
      <p v-if="lockedAdmin" class="hint">Konto <strong>admin</strong> zawsze ma uprawnienia administratora.</p>
      <label>{{ state.editingUser ? 'Nowe hasło (opcjonalnie)' : 'Hasło startowe (opcjonalnie)' }}
        <input v-model="form.password" type="password" autocomplete="new-password" placeholder="Domyślnie: password" />
      </label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</template>
