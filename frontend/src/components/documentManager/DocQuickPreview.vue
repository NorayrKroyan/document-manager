<template>
  <teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50">
      <!-- overlay -->
      <div class="absolute inset-0 bg-black/40" @click="$emit('close')" />

      <!-- dialog -->
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div
            class="w-full max-w-4xl rounded-2xl bg-white shadow-xl overflow-hidden
                 flex flex-col"
            style="height: 75vh;"
            @click.stop
        >
          <!-- HEADER -->
          <div class="flex items-center justify-between gap-3 border-b px-4 py-3">
            <div class="min-w-0">
              <div class="text-base font-semibold text-gray-900 truncate">
                {{ title }}
              </div>
            </div>

            <div class="flex items-center gap-2">
              <a
                  v-if="url"
                  :href="url"
                  target="_blank"
                  class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm hover:bg-gray-50"
              >
                Open
              </a>

              <button
                  type="button"
                  class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800"
                  @click="$emit('close')"
              >
                Close
              </button>
            </div>
          </div>

          <!-- BODY -->
          <div class="flex-1 p-3 overflow-hidden">
            <div class="h-full w-full rounded-xl border bg-white overflow-hidden flex items-center justify-center">
              <div v-if="!url" class="p-3 text-sm text-gray-500">No file.</div>

              <!-- Images -->
              <img
                  v-else-if="isImage"
                  :src="url"
                  alt="Preview"
                  class="max-h-full max-w-full object-contain bg-gray-50"
                  draggable="false"
              />

              <!-- PDFs / other -->
              <iframe
                  v-else
                  :src="url"
                  class="h-full w-full block"
                  frameborder="0"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Preview' },
  url: { type: String, default: '' },
})

defineEmits(['close'])

const isImage = computed(() => {
  const u = String(props.url || '').toLowerCase()
  return (
      u.endsWith('.png') ||
      u.endsWith('.jpg') ||
      u.endsWith('.jpeg') ||
      u.endsWith('.gif') ||
      u.endsWith('.webp') ||
      u.endsWith('.bmp') ||
      u.endsWith('.svg')
  )
})
</script>