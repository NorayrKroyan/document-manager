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

    <!-- SEARCH + STATUS -->
    <div class="mt-1 flex flex-col gap-2 md:flex-row md:items-center md:gap-3">
      <div class="w-full md:max-w-[520px]">
        <input
            v-model="dm.q.value"
            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            placeholder="Search documents (name, description, owner...)"
        />
      </div>

      <div class="w-full md:w-auto">
        <select
            v-model="dm.status.value"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm"
        >
          <option value="active">Active</option>
          <option value="deleted">Deleted</option>
        </select>
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
      <!-- HEADER ROW (Actions = 2nd col) -->
      <div
          class="grid grid-cols-[1.6fr_0.7fr_1.2fr_1.0fr_1.2fr_0.9fr]
               gap-3 rounded-md bg-gray-200 px-3 py-2
               text-[18px] font-bold text-gray-900"
      >
        <div>Document</div>
        <div>Actions</div>
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
              :class="rowClass(idx)"
              :title="dm.status.value === 'deleted' ? 'Restore this document to edit it' : ''"
              @click="onRowClick(r)"
          >
            <div
                class="grid grid-cols-[1.6fr_0.7fr_1.2fr_1.0fr_1.2fr_0.9fr]
                     gap-3 px-1 py-2 text-xs"
            >
              <!-- Document -->
              <div class="min-w-0">
                <div class="truncate font-medium text-blue-700 hover:underline">
                  {{ r.doc_name || '—' }}
                </div>
              </div>

              <!-- Actions (icons) -->
              <div class="flex items-center gap-3">
                <!-- View -->
                <button
                    type="button"
                    class="text-gray-500 hover:text-blue-600 disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="!r.file_url"
                    @click.stop="onQuickView(r)"
                    title="View"
                >
                  <svg xmlns="http://www.w3.org/2000/svg"
                       class="h-5 w-5"
                       fill="none"
                       viewBox="0 0 24 24"
                       stroke="currentColor"
                       stroke-width="1.8">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.5 4-5 7-9 7s-7.5-3-9-7c1.5-4 5-7 9-7s7.5 3 9 7z" />
                  </svg>
                </button>

                <!-- Restore icon ONLY when status=deleted -->
                <button
                    v-if="dm.status.value === 'deleted'"
                    type="button"
                    class="text-gray-500 hover:text-emerald-600 disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="dm.loading.value"
                    @click.stop="onRestore(r)"
                    title="Restore"
                >
                  <svg xmlns="http://www.w3.org/2000/svg"
                       class="h-5 w-5"
                       fill="none"
                       viewBox="0 0 24 24"
                       stroke="currentColor"
                       stroke-width="1.8">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M9 14l-4-4 4-4M5 10h9a5 5 0 015 5v1" />
                  </svg>
                </button>

                <!-- Download icon when NOT deleted filter -->
                <button
                    v-else
                    type="button"
                    class="text-gray-500 hover:text-green-600 disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="!r.file_url"
                    @click.stop="onDownload(r)"
                    title="Download"
                >
                  <svg xmlns="http://www.w3.org/2000/svg"
                       class="h-5 w-5"
                       fill="none"
                       viewBox="0 0 24 24"
                       stroke="currentColor"
                       stroke-width="1.8">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                  </svg>
                </button>
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
          <div v-if="dm.loading.value" class="py-4 text-sm text-gray-500">
            Loading...
          </div>
        </div>
      </div>

      <!-- PAGINATION -->
      <div class="border-t border-gray-100 px-3 py-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="text-xs text-gray-500">
            Page {{ dm.page.value }} / {{ dm.totalPages.value }} ({{ dm.total.value }} total)
          </div>

          <div class="flex flex-wrap items-center justify-end gap-2">
            <!-- Page size -->
            <select
                v-model.number="dm.limit.value"
                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs"
            >
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>

            <!-- First -->
            <button
                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
                :disabled="dm.page.value <= 1 || dm.loading.value"
                @click="goFirst"
            >
              First
            </button>

            <!-- Prev -->
            <button
                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
                :disabled="dm.page.value <= 1 || dm.loading.value"
                @click="goPrev"
            >
              Prev
            </button>

            <!-- Jump -->
            <div class="flex items-center gap-2">
              <span class="text-xs text-gray-500">Go to</span>
              <input
                  v-model="pageInput"
                  inputmode="numeric"
                  class="w-16 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-center"
                  :disabled="dm.loading.value"
                  @keydown.enter.prevent="applyPageInput"
              />
              <button
                  class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
                  :disabled="dm.loading.value"
                  @click="applyPageInput"
              >
                Go
              </button>
            </div>

            <!-- Next -->
            <button
                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
                :disabled="!dm.canNext.value || dm.loading.value"
                @click="goNext"
            >
              Next
            </button>

            <!-- Last -->
            <button
                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
                :disabled="dm.page.value >= dm.totalPages.value || dm.loading.value"
                @click="goLast"
            >
              Last
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- QUICK PREVIEW POPUP -->
    <DocQuickPreview
        :open="previewOpen"
        :title="previewTitle"
        :url="previewUrl"
        @close="previewOpen = false"
    />

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
import { onMounted, watch, ref } from 'vue'
import { useDocumentManagerPage } from '@/composables/useDocumentManagerPage'
import { useDocumentModal } from '@/composables/useDocumentModal'
import DocumentModal from '@/components/documentManager/DocumentModal.vue'
import DocQuickPreview from '@/components/documentManager/DocQuickPreview.vue'
import { dmRestoreDocument } from '@/api/document-manager'

const dm = useDocumentManagerPage()
const docModal = useDocumentModal()

const previewOpen = ref(false)
const previewUrl = ref('')
const previewTitle = ref('')

// pagination
const pageInput = ref('')

function clampPage(n) {
  const max = Number(dm.totalPages.value || 1)
  const x = Math.max(1, Math.min(max, Number(n || 1)))
  return Number.isFinite(x) ? x : 1
}
function syncPageInput() {
  pageInput.value = String(dm.page.value || 1)
}
function goFirst() { dm.page.value = 1 }
function goLast() { dm.page.value = Number(dm.totalPages.value || 1) }
function goPrev() { dm.page.value = clampPage(Number(dm.page.value || 1) - 1) }
function goNext() { dm.page.value = clampPage(Number(dm.page.value || 1) + 1) }
function applyPageInput() {
  dm.page.value = clampPage(parseInt(pageInput.value, 10))
}

// ✅ prevent editing deleted records (this fixes your “Not found” save)
function onRowClick(r) {
  if (dm.status.value === 'deleted') return
  docModal.openEdit(r)
}
function rowClass(idx) {
  const base = idx % 2 ? 'bg-white hover:bg-gray-100' : 'bg-gray-50 hover:bg-gray-100'
  if (dm.status.value !== 'deleted') return base
  // deleted mode: no hover emphasis, cursor not allowed
  return (idx % 2 ? 'bg-white' : 'bg-gray-50') + ' opacity-90 cursor-not-allowed'
}

function onQuickView(r) {
  const url = r?.file_url || ''
  if (!url) return
  previewTitle.value = r?.doc_name || 'Preview'
  previewUrl.value = url
  previewOpen.value = true
}

function onDownload(r) {
  const url = r?.file_url || ''
  if (!url) return

  const a = document.createElement('a')
  a.href = url
  const safeName = String(r?.doc_name || 'document').trim() || 'document'
  a.download = safeName
  document.body.appendChild(a)
  a.click()
  a.remove()
}

async function onRestore(r) {
  const id = Number(r?.id_document || 0)
  if (!id) return
  const res = await dmRestoreDocument(id)
  if (res?.ok) await dm.reloadList()
}

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

watch(
    () => dm.status.value,
    async () => {
      dm.page.value = 1
      await dm.reloadList()
    }
)

watch(
    () => dm.page.value,
    () => syncPageInput(),
    { immediate: true }
)

watch(
    () => dm.totalPages.value,
    () => {
      dm.page.value = clampPage(dm.page.value)
      syncPageInput()
    }
)

onMounted(async () => {
  await dm.boot()
  syncPageInput()
})
</script>