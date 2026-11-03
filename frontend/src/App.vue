<script setup>
import { onMounted } from 'vue'
import { closeModal, pingReminders, showView, state, submitDelete, submitPurge } from './store'
import FmeaView from './components/FmeaView.vue'
import CpView from './components/CpView.vue'
import UsersView from './components/UsersView.vue'
import DetailDrawer from './components/DetailDrawer.vue'
import ReviewModal from './components/ReviewModal.vue'
import FmeaModal from './components/FmeaModal.vue'
import CpModal from './components/CpModal.vue'
import SettingsModal from './components/SettingsModal.vue'
import PhraseModal from './components/PhraseModal.vue'
import UserModal from './components/UserModal.vue'
import PersonModal from './components/PersonModal.vue'
const base = import.meta.env.BASE_URL

onMounted(() => {
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
    <img class="brandbar-logo" :src="`${base}logo-autoneum.png`" alt="Autoneum" />
  </nav>

  <FmeaView v-if="state.view === 'fmea'" />
  <CpView v-else-if="state.view === 'cp'" />
  <UsersView v-else />

  <DetailDrawer />
  <ReviewModal />
  <FmeaModal />
  <CpModal />
  <SettingsModal />
  <UserModal />
  <PersonModal />

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
