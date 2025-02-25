import { ref, onMounted, onUnmounted } from 'vue'

export function useAvailableSpace() {
  const availableHeight = ref(0)

  const calculateAvailableSpace = () => {
    const layoutContent = document.querySelector('.layout-page-content')
    if (!layoutContent) return

    const layoutTop = layoutContent.getBoundingClientRect().top
    const windowHeight = window.innerHeight
    availableHeight.value = windowHeight - layoutTop
  }

  onMounted(() => {
    calculateAvailableSpace()
    window.addEventListener('resize', calculateAvailableSpace)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', calculateAvailableSpace)
  })

  return {
    availableHeight
  }
} 