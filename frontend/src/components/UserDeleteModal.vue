<script setup>
import { computed } from 'vue'
import { userName } from '../format'
import {
  applyBulkUserDeleteReplacement,
  assignmentKey,
  closeModal,
  state,
  submitDeleteUser,
  toggleUserDeleteRow,
} from '../store'

const deleting = computed(() => state.deletingUser)
const rows = computed(() => state.userDeleteAssignments)
const candidates = computed(() => state.users.filter((user) => user.id !== deleting.value?.id))

function optionLabel(user) {
  return `${userName(user)}${user.dzial ? ` · ${user.dzial}` : ''}`
}

function kindLabel(kind) {
  return kind === 'fmea' ? 'FMEA' : 'Plan kontroli'
}

function allSelected() {
  return rows.value.length > 0 && rows.value.every((row) => state.userDeleteSelected.has(assignmentKey(row)))
}

function toggleAll(event) {
  const checked = event.target.checked
  for (const row of rows.value) {
    toggleUserDeleteRow(assignmentKey(row), checked)
  }
}

async function onSubmit() {
  try {
    await submitDeleteUser()
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'deleteUser' && deleting" class="modal-backdrop" @click.self="closeModal">
    <form class="modal modal-scroll modal-wide" @submit.prevent="onSubmit">
      <h3>Usuwanie użytkownika {{ userName(deleting) }}</h3>
      <p v-if="!rows.length" class="hint">Użytkownik nie jest przypisany do żadnej pozycji. Możesz go usunąć.</p>
      <template v-else>
        <p class="hint">Przypisania trzeba przepisać na innego użytkownika. Przy wielu osobach w jednym polu zostaną pozostali.</p>
        <div class="bulk-bar">
          <label class="bulk-field">
            <span>Zaznacz wszystkie</span>
            <input type="checkbox" :checked="allSelected()" @change="toggleAll" />
          </label>
          <label class="bulk-field grow">
            Grupowe zastępstwo
            <select v-model="state.userDeleteBulkUserId">
              <option value="">— wybierz —</option>
              <option v-for="user in candidates" :key="user.id" :value="String(user.id)">{{ optionLabel(user) }}</option>
            </select>
          </label>
          <button type="button" class="btn small" @click="applyBulkUserDeleteReplacement">Ustaw dla zaznaczonych</button>
        </div>
        <div class="table-wrap nested-table">
          <table>
            <thead>
              <tr>
                <th></th>
                <th>Rejestr</th>
                <th>Numer</th>
                <th>Rola</th>
                <th>Obecna wartość</th>
                <th>Zastępca</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="assignmentKey(row)">
                <td>
                  <input
                    type="checkbox"
                    :checked="state.userDeleteSelected.has(assignmentKey(row))"
                    @change="toggleUserDeleteRow(assignmentKey(row), $event.target.checked)"
                  />
                </td>
                <td>{{ kindLabel(row.kind) }}</td>
                <td>{{ row.label }}</td>
                <td>{{ row.role }}</td>
                <td>{{ row.current || '—' }}</td>
                <td>
                  <select v-model="state.userDeleteReplacements[assignmentKey(row)]" required>
                    <option value="">— wybierz —</option>
                    <option v-for="user in candidates" :key="user.id" :value="String(user.id)">{{ optionLabel(user) }}</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn danger">Usuń użytkownika</button>
      </div>
    </form>
  </div>
</template>
