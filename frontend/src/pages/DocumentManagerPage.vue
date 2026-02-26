<template>
  <div class="p-6">
    <!-- HEADER -->
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Document Manager</h1>
      </div>

      <button
          class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
          @click="docModal.openCreate()"
      >
        New Document
      </button>
    </div>

    <!-- SEARCH -->
    <div class="mt-1">
      <div class="w-full md:max-w-[520px]">
        <input
            v-model="dm.q.value"
            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            placeholder="Search documents (name, path, description...)"
        />
      </div>
    </div>

    <!-- ERROR -->
    <div
        v-if="dm.err.value"
        class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    >
      {{ dm.err.value }}
    </div>

    <!-- CARD -->
    <div class="mt-1 rounded-xl bg-white shadow-sm">

      <!-- HEADER ROW -->
      <div
          class="grid grid-cols-[1.4fr_1.2fr_0.9fr_0.9fr_0.8fr_0.7fr]
               gap-3 rounded-md bg-gray-200 px-3 py-2
               text-[18px] font-bold text-gray-900"
      >
        <div>Document</div>
        <div>Path</div>
        <div>Type</div>
        <div>Owner Type</div>
        <div>Owner</div>
        <div class="text-right">Expiration</div>
      </div>

      <!-- LIST -->
      <div class="pb-3">
        <div class="space-y-0">
          <button
              v-for="(r, idx) in dm.list.value"
              :key="r.id_document"
              type="button"
              class="w-full rounded-md text-left transition-colors"
              :class="idx % 2 ? 'bg-white hover:bg-gray-100' : 'bg-gray-50 hover:bg-gray-100'"
              @click="docModal.openEdit(r)"
          >
            <div
                class="grid grid-cols-[1.4fr_1.2fr_0.9fr_0.9fr_0.8fr_0.7fr]
                     gap-3 px-1 py-2 text-xs"
            >

              <!-- Document -->
              <div class="min-w-0">
                <div class="truncate font-medium text-blue-700 hover:underline">
                  {{ r.doc_name || '—' }}
                </div>
              </div>

              <!-- Path -->
              <div class="truncate text-[11px] text-gray-500">
                {{ r.path || '—' }}
              </div>

              <!-- Type -->
              <div class="truncate text-gray-700">
                {{ r.doctype_name || '—' }}
              </div>

              <!-- Owner Type -->
              <div class="truncate text-gray-700">
                {{ r.owner_type_name || '—' }}
              </div>

              <!-- Owner -->
              <!-- Owner -->
              <div class="truncate text-gray-700">
                {{ r.owner_name || '—' }}
              </div>

              <!-- Expiration -->
              <div class="truncate text-gray-700 text-right">
                {{ r.doc_expiration || '—' }}
              </div>

            </div>
          </button>

          <!-- EMPTY -->
          <div
              v-if="!dm.list.value.length && !dm.loading.value"
              class="py-4 text-sm text-gray-500"
          >
            No documents.
          </div>

          <!-- LOADING -->
          <div
              v-if="dm.loading.value"
              class="py-4 text-sm text-gray-500"
          >
            Loading...
          </div>
        </div>
      </div>

      <!-- PAGINATION -->
      <div class="border-t border-gray-100 px-3 py-4">
        <div class="flex items-center justify-between gap-3">
          <div class="text-xs text-gray-500">
            Page {{ dm.page.value }} /
            {{ dm.totalPages.value }}
            ({{ dm.total.value }} total)
          </div>

          <div class="flex items-center gap-2">
            <select
                v-model.number="dm.limit.value"
                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs"
            >
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>

            <button
                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
                :disabled="dm.page.value <= 1 || dm.loading.value"
                @click="dm.page.value -= 1"
            >
              Prev
            </button>

            <button
                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
                :disabled="!dm.canNext.value || dm.loading.value"
                @click="dm.page.value += 1"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL -->
    <DocumentModal
        :open="docModal.open.value"
        :mode="docModal.mode.value"
        :saving="docModal.saving.value || docModal.deleting.value"
        :errors="docModal.errors.value"
        :form="docModal.form"
        :lookups="dm.lookups.value"
        @close="docModal.close()"
        @save="docModal.save().then(afterSave)"
        @delete="docModal.del().then(afterDelete)"
    />
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { useDocumentManagerPage } from '@/composables/useDocumentManagerPage'
import { useDocumentModal } from '@/composables/useDocumentModal'
import DocumentModal from '@/components/documentManager/DocumentModal.vue'

const dm = useDocumentManagerPage()
const docModal = useDocumentModal()

async function afterSave(res) {
  if (!res?.ok) return
  await dm.reloadList()
}

async function afterDelete(res) {
  if (!res?.ok) return
  await dm.reloadList()
}

let t = null
watch(
    () => dm.q.value,
    () => {
      dm.page.value = 1
      if (t) clearTimeout(t)
      t = setTimeout(() => dm.reloadList(), 350)
    }
)

watch(
    () => [dm.page.value, dm.limit.value],
    () => dm.reloadList()
)

onMounted(dm.boot)
</script>