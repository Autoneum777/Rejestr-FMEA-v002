import { onBeforeUnmount, onMounted } from 'vue'

export function useStickyHead(table) {
  function sync() {
    const head = table.value?.querySelector('thead tr:first-child')
    if (head?.offsetHeight) table.value.style.setProperty('--head-h', `${head.offsetHeight}px`)
  }
  onMounted(() => {
    sync()
    window.addEventListener('resize', sync)
  })
  onBeforeUnmount(() => window.removeEventListener('resize', sync))
  return { sync }
}
