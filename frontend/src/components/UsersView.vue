<script setup>
import { computed, onMounted } from 'vue'
import { userName } from '../format'
import { deleteUser, loadUsers, openUserForm, state } from '../store'

const hint = computed(() => (
  state.mailReady
    ? `Powiadomienia wychodzą z ${state.mailFrom} przez ${state.mailHost}. E-mail i dział uzupełniasz przy użytkowniku.`
    : 'Osoba z Excela (Imię Nazwisko albo M.Nazwisko) pojawia się tutaj automatycznie. E-mail i dział uzupełniasz ręcznie. Miesiąc przed terminem wychodzi jedna wiadomość, a po terminie przypomnienie co tydzień. Wysyłka ruszy po uzupełnieniu api/data/mail.json.'
))

onMounted(async () => {
  try {
    await loadUsers()
  } catch (err) {
    state.usersError = err.message
  }
})

function remove(user) {
  deleteUser(user).catch((err) => alert(err.message))
}
</script>

<template>
  <header class="top">
    <div>
      <p class="eyebrow">Quality · Powiadomienia</p>
      <h1>Użytkownicy</h1>
      <p class="hint">{{ hint }}</p>
    </div>
    <div class="top-actions">
      <button type="button" class="btn" @click="openUserForm(null)">Nowy użytkownik</button>
    </div>
  </header>

  <main class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Imię</th>
          <th>Nazwisko</th>
          <th>Dział</th>
          <th>E-mail</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="state.usersError"><td colspan="5">{{ state.usersError }}</td></tr>
        <tr v-else-if="!state.users.length">
          <td colspan="5" class="muted">Brak użytkowników. Pojawią się po wczytaniu Excela z osobą Imię Nazwisko albo M.Nazwisko.</td>
        </tr>
        <tr v-for="user in state.users" :key="user.id">
          <td>{{ user.imie }}</td>
          <td>{{ user.nazwisko }}</td>
          <td>{{ user.dzial || '—' }}</td>
          <td>{{ user.email || '—' }}</td>
          <td class="row-actions">
            <button type="button" class="btn small ghost" @click="openUserForm(user)">Edytuj</button>
            <button type="button" class="btn small danger" @click="remove(user)">Usuń</button>
          </td>
        </tr>
      </tbody>
    </table>
  </main>
</template>
