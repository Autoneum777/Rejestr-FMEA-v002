<script setup>
defineProps({
  summary: { type: Object, default: null },
  cards: { type: Array, required: true },
})
</script>

<template>
  <section v-if="summary" class="stats">
    <div v-for="card in cards" :key="card.key" class="stat" :class="card.className">
      <b>{{ summary[card.key] ?? 0 }}</b>
      <span>{{ card.label }}</span>
    </div>
  </section>
</template>
