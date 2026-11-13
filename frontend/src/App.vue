<script setup>
import { computed, onMounted } from 'vue'
import {
  closeModal,
  isAdmin,
  applyAuth,
  loadAuth,
  logout,
  openLogin,
  openPassword,
  pingReminders,
  showView,
  state,
  submitDelete,
  submitPurge,
} from './store'
import FmeaView from './components/FmeaView.vue'
import CpView from './components/CpView.vue'
import UsersView from './components/UsersView.vue'
import AdminView from './components/AdminView.vue'
import DetailDrawer from './components/DetailDrawer.vue'
import ReviewModal from './components/ReviewModal.vue'
import FmeaModal from './components/FmeaModal.vue'
import CpModal from './components/CpModal.vue'
import SettingsModal from './components/SettingsModal.vue'
import PhraseModal from './components/PhraseModal.vue'
import UserModal from './components/UserModal.vue'
import PersonModal from './components/PersonModal.vue'
import UserDeleteModal from './components/UserDeleteModal.vue'
import LoginModal from './components/LoginModal.vue'
import PasswordModal from './components/PasswordModal.vue'
const base = import.meta.env.BASE_URL

const authLabel = computed(() => {
  if (state.auth.role === 'guest') return 'Gość (tylko odczyt)'
  if (state.auth.role === 'admin') return `Administrator · ${state.auth.login}`
  return `${state.auth.name || state.auth.login}`
})

onMounted(() => {
  loadAuth().catch(() => applyAuth({ role: 'guest' }))
  pingReminders()
})

async function onDelete(typed) {
  try {
    await submitDelete(typed)
  } catch (err) {
    alert(err.message)
  }
}

async function onPurge(typed) {
  try {
    await submitPurge(typed)
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <nav class="brandbar">
    <span class="brandbar-app">Quality</span>
    <button type="button" class="brandbar-tab" :class="{ active: state.view === 'fmea' }" @click="showView('fmea')">Rejestr FMEA</button>
    <button type="button" class="brandbar-tab" :class="{ active: state.view === 'cp' }" @click="showView('cp')">Rejestr planów kontroli</button>
    <button type="button" class="brandbar-tab" :class="{ active: state.view === 'users' }" @click="showView('users')">Użytkownicy</button>
    <button v-if="isAdmin()" type="button" class="brandbar-tab" :class="{ active: state.view === 'admin' }" @click="showView('admin')">Administracja</button>
    <p class="brandbar-auth">{{ authLabel }}</p>
    <button v-if="state.auth.role === 'guest'" type="button" class="btn small ghost brandbar-btn" @click="openLogin">Zaloguj</button>
    <template v-else>
      <button type="button" class="btn small ghost brandbar-btn" @click="openPassword">Hasło</button>
      <button type="button" class="btn small ghost brandbar-btn" @click="logout">Wyloguj</button>
    </template>
    <img class="brandbar-logo" :src="`${base}logo-autoneum.png`" alt="Autoneum" />
  </nav>

  <FmeaView v-if="state.view === 'fmea'" />
  <CpView v-else-if="state.view === 'cp'" />
  <UsersView v-else-if="state.view === 'users'" />
  <AdminView v-else />

  <DetailDrawer />
  <ReviewModal />
  <FmeaModal />
  <CpModal />
  <SettingsModal />
  <UserModal />
  <PersonModal />
  <UserDeleteModal />
  <LoginModal />
  <PasswordModal />

  <PhraseModal
    :open="state.modal === 'purge'"
    :title="state.purgeKind === 'fmea' ? 'Wyczyszczenie rejestru FMEA' : 'Wyczyszczenie planów kontroli'"
    :hint="`Aby potwierdzić, wpisz: ${state.purgePhrase}`"
    :phrase="state.purgePhrase"
    confirm-label="Wyczyść na zawsze"
    @cancel="closeModal"
    @submit="onPurge"
  />
  <PhraseModal
    :open="state.modal === 'delete'"
    title="Usunięcie bezpowrotne"
    :hint="`Aby potwierdzić, wpisz numer FMEA: ${state.deleteNr}`"
    :phrase="state.deleteNr"
    label="Wpisz numer FMEA, aby potwierdzić"
    confirm-label="Usuń na zawsze"
    @cancel="closeModal"
    @submit="onDelete"
  />
</template>
