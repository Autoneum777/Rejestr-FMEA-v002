<script setup>
import { reactive, watch } from 'vue'
import { today } from '../format'
import { closeModal, state, submitReview } from '../store'

const form = reactive({
  review_date: '',
  review_type: '',
  reason: '',
  comment: '',
})

watch(() => state.modal, (modal) => {
  if (modal !== 'review') return
  form.review_date = today()
  form.review_type = ''
  form.reason = ''
  form.comment = ''
})

async function onSubmit() {
  if (!form.reason.trim()) {
    alert('Podaj powód rewizji.')
    return
  }
  try {
    await submitReview({
      review_date: form.review_date,
      review_type: form.review_type,
      reason: form.reason,
      comment: form.comment,
    })
  } catch (err) {
    alert(err.message)
  }
}
</script>

<template>
  <div v-if="state.modal === 'review'" class="modal-backdrop" @click.self="closeModal">
    <form class="modal" @submit.prevent="onSubmit">
      <h3>{{ state.reviewKind === 'cp' ? 'Aktualizacja daty planu kontroli' : 'Aktualizacja daty FMEA' }}</h3>
      <p class="hint">Przegląd zdarzeniowy zapisuje się w historii i nie przesuwa terminu kolejnej pełnej rewizji.</p>
      <label>Data przeglądu
        <input v-model="form.review_date" type="date" required />
      </label>
      <fieldset>
        <legend>Typ przeglądu</legend>
        <label class="choice">
          <input v-model="form.review_type" type="radio" value="pelny" required />
          <span><strong>Pełny</strong> — resetuje termin kolejnej rewizji (+12 mies.) i ostrzeżenie (+11 mies.)</span>
        </label>
        <label class="choice">
          <input v-model="form.review_type" type="radio" value="zdarzeniowy" />
          <span><strong>Zdarzeniowy</strong> — bez wpływu na termin ostateczny kolejnej rewizji</span>
        </label>
      </fieldset>
      <label>Powód rewizji
        <input v-model="form.reason" type="text" required placeholder="np. Przegląd roczny, RN12/26…" />
      </label>
      <label>Komentarz (opcjonalnie)
        <textarea v-model="form.comment" rows="2"></textarea>
      </label>
      <div class="modal-actions">
        <button type="button" class="btn ghost" @click="closeModal">Anuluj</button>
        <button type="submit" class="btn">Zapisz przegląd</button>
      </div>
    </form>
  </div>
</template>
