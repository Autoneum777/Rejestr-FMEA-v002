<script setup>
import { ref } from 'vue'
import { importCp, importFmea, openPurge, openSettings } from '../store'

const fmeaFile = ref(null)
const cpFile = ref(null)
const fmeaFormat = ref('legacy')
const cpFormat = ref('legacy')

function pickFmea(format) {
  fmeaFormat.value = format
  fmeaFile.value?.click()
}

function pickCp(format) {
  cpFormat.value = format
  cpFile.value?.click()
}

async function onFmeaFile(event) {
  const picked = event.target.files?.[0]
  event.target.value = ''
  if (!picked) return
  try {
    await importFmea(picked, fmeaFormat.value)
  } catch (err) {
    alert(err.message)
  }
}

async function onCpFile(event) {
  const picked = event.target.files?.[0]
  event.target.value = ''
  if (!picked) return
  try {
    await importCp(picked, cpFormat.value)
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <header class="top">
    <div>
      <p class="eyebrow">Quality · Administracja</p>
      <h1>Administracja</h1>
      <p class="hint">Import Excela, czyszczenie baz i kolorystyka — tylko dla konta administratora.</p>
    </div>
  </header>

  <main class="admin-grid">
    <section class="admin-card">
      <h2>Kolorystyka statusów</h2>
      <p class="muted">Kolory wierszy w rejestrach i eksporcie Excela.</p>
      <button type="button" class="btn" @click="openSettings">Ustaw kolory</button>
    </section>

    <section class="admin-card">
      <h2>Import rejestru FMEA</h2>
      <p class="muted">Nadpisuje wszystkie pozycje FMEA. Wybierz układ pliku zgodny ze źródłem.</p>
      <div class="admin-actions">
        <button type="button" class="btn" @click="pickFmea('legacy')">Format oryginalny</button>
        <button type="button" class="btn ghost" @click="pickFmea('app')">Struktura z eksportu aplikacji</button>
      </div>
      <input ref="fmeaFile" type="file" accept=".xlsx,.xlsm" hidden @change="onFmeaFile" />
    </section>

    <section class="admin-card">
      <h2>Import planów kontroli</h2>
      <p class="muted">Nadpisuje plany kontroli. Najpierw FMEA, potem plany — jeśli importujesz oba rejestry.</p>
      <div class="admin-actions">
        <button type="button" class="btn" @click="pickCp('legacy')">Format oryginalny</button>
        <button type="button" class="btn ghost" @click="pickCp('app')">Struktura z eksportu aplikacji</button>
      </div>
      <input ref="cpFile" type="file" accept=".xlsx,.xlsm" hidden @change="onCpFile" />
    </section>

    <section class="admin-card danger-zone">
      <h2>Czyszczenie baz</h2>
      <p class="muted">Trwale usuwa pozycje i historię. Użytkownicy pozostają.</p>
      <div class="admin-actions">
        <button type="button" class="btn danger" @click="openPurge('fmea')">Wyczyść rejestr FMEA</button>
        <button type="button" class="btn danger" @click="openPurge('cp')">Wyczyść plany kontroli</button>
      </div>
    </section>
  </main>
</template>
