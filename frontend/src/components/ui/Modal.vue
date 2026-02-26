<template>
  <teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/40" @click="$emit('close')" />

      <!-- ✅ keep padding, but center the modal by translate (stable during resize) -->
      <div class="absolute inset-0 p-2 sm:p-4">
        <div
            ref="boxEl"
            class="absolute left-1/2 top-1/2 w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-xl -translate-x-1/2 -translate-y-1/2"
            :style="boxStyle"
        >
          <!-- header -->
          <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-4 py-3">
            <div class="min-w-0">
              <div v-if="subtitle" class="text-sm text-gray-500">{{ subtitle }}</div>
              <div class="text-xl font-semibold text-gray-900 truncate">{{ title }}</div>
            </div>

            <button
                type="button"
                class="rounded-lg px-2 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                @click="$emit('close')"
            >
              ✕
            </button>
          </div>

          <!-- body -->
          <div class="max-h-[78vh] overflow-auto px-4 py-3">
            <slot />
          </div>

          <!-- footer -->
          <div class="border-t border-gray-200 px-4 py-3">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { ref, computed } from 'vue'

defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
})
defineEmits(['close'])

const boxEl = ref(null)

// optional size overrides (only applied if setSize() called)
const wPx = ref(null) // number|null
const hPx = ref(null) // number|null

const boxStyle = computed(() => {
  const s = {}
  if (Number.isFinite(wPx.value)) s.width = `${Math.max(320, Math.round(wPx.value))}px`
  if (Number.isFinite(hPx.value)) s.height = `${Math.max(240, Math.round(hPx.value))}px`
  return s
})

function getSize() {
  const el = boxEl.value
  if (!el) return { width: 0, height: 0 }
  const r = el.getBoundingClientRect()
  return { width: r.width, height: r.height }
}

function setSize(width, height) {
  const minW = 320
  const minH = 240

  const maxW = Math.max(minW, window.innerWidth - 24)
  const maxH = Math.max(minH, window.innerHeight - 24)

  const nextW = Math.min(maxW, Math.max(minW, Number(width || 0)))
  const nextH = Math.min(maxH, Math.max(minH, Number(height || 0)))

  wPx.value = nextW
  hPx.value = nextH
}

defineExpose({ getSize, setSize })
</script>