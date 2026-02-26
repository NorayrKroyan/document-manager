<!-- resources/js/components/documentManager/DocumentModal.vue -->
<template>
  <Modal
      ref="modalRef"
      :open="open"
      :title="mode === 'edit' ? 'Edit Document' : 'New Document'"
      :subtitle="''"
      @close="onClose"
  >
    <div class="mx-auto w-full max-w-5xl space-y-3">
      <div
          v-if="errors?._general"
          class="mb-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      >
        {{ errors._general }}
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-[max-content_1fr]">
        <!-- LEFT: fields -->
        <div class="space-y-3 w-max">
          <RowField label="Document Name:" :err="errors?.doc_name" v-slot="{ hasError }">
            <input
                v-model="form.doc_name"
                type="text"
                class="cd-input rounded-xl border px-4 py-3 text-sm outline-none"
                :class="hasError ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'"
                placeholder="Document Name"
            />
          </RowField>

          <RowField label="Description:" :err="errors?.doc_description" v-slot="{ hasError }">
            <textarea
                v-model="form.doc_description"
                rows="2"
                class="cd-input rounded-xl border px-4 py-3 text-sm outline-none"
                :class="hasError ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'"
                placeholder="Optional"
            />
          </RowField>

          <RowField label="Document Type:" :err="errors?.id_doctype" v-slot="{ hasError }">
            <vSelect
                v-model="form.id_doctype"
                :options="lookups?.doctypes || []"
                :reduce="o => o.id"
                :get-option-label="o => (o?.name ?? '')"
                :clearable="true"
                placeholder="Select type"
                class="cd-input"
                :class="hasError ? 'v-select-error' : ''"
            />
          </RowField>

          <RowField label="Owner Type:" :err="errors?.id_docowner" v-slot="{ hasError }">
            <vSelect
                v-model="form.id_docowner"
                :options="lookups?.docowners || []"
                :reduce="o => o.id"
                :get-option-label="o => (o?.name ?? '')"
                :clearable="true"
                placeholder="Select owner type"
                class="cd-input"
                :class="hasError ? 'v-select-error' : ''"
            />
          </RowField>

          <RowField label="Owner:" :err="errors?.id_owner" v-slot="{ hasError }">
            <vSelect
                v-model="form.id_owner"
                :options="mergedOwnerOptions"
                :reduce="o => Number(o.id)"
                :get-option-label="o => o.name"
                :append-to-body="true"
                :clearable="true"
                placeholder="Search owner"
                class="cd-input"
                :class="hasError ? 'v-select-error' : ''"
                :filterable="false"
                :loading="ownersLoading"
                @open="onOwnerOpen"
                @search="onOwnerSearch"
                @option:selected="onOwnerSelected"
                @clear="onOwnerCleared"
            />
          </RowField>

          <!-- ✅ Expiration shows ONLY when doctype.require_expire == 1 -->
          <RowField
              v-if="showExpiration"
              label="Expiration:"
              :err="errors?.doc_expiration"
              v-slot="{ hasError }"
          >
            <div class="cd-input">
              <input
                  v-model="form.doc_expiration"
                  type="date"
                  class="w-full rounded-xl border px-4 py-3 text-sm outline-none"
                  :class="hasError ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'"
              />
            </div>
          </RowField>

          <RowField label="Upload File:" :err="errors?.file" v-slot="{ hasError }">
            <div class="cd-input">
              <input
                  ref="fileInputRef"
                  type="file"
                  class="block w-full text-sm"
                  :class="hasError ? 'text-red-700' : 'text-gray-700'"
                  @change="onPickFile"
              />

              <div v-if="currentFileName" class="mt-1 text-xs text-gray-700 font-medium">
                Current file: {{ currentFileName }}
              </div>

              <div class="mt-1 text-xs text-gray-500">
                {{ mode === 'edit'
                  ? 'Choose a file to replace the current one.'
                  : 'File is required on create.' }}
              </div>
            </div>
          </RowField>
        </div>

        <!-- RIGHT: preview -->
        <div class="space-y-2 min-w-0 w-full">
          <div class="flex items-center justify-between">
            <div class="text-sm font-semibold text-gray-800">Preview</div>

            <a
                v-if="openUrl"
                :href="openUrl"
                target="_blank"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm hover:bg-gray-50"
            >
              Open
            </a>
          </div>

          <div class="w-full rounded-xl bg-white p-2">
            <div
                ref="viewerEl"
                class="w-full rounded-lg border bg-white overflow-hidden"
                style="resize: both; height: 520px; min-height: 320px; min-width: 320px;"
            >
              <div v-if="!previewUrl" class="p-3 text-sm text-gray-500">
                No file selected / uploaded yet.
              </div>

              <iframe
                  v-else
                  :src="previewUrl"
                  class="w-full h-full block"
                  frameborder="0"
                  scrolling="no"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex w-full items-center justify-between gap-3">
        <button
            v-if="mode === 'edit'"
            type="button"
            class="rounded-xl border border-red-300 bg-white px-6 py-3 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
            :disabled="saving"
            @click="$emit('delete')"
        >
          Delete
        </button>
        <div v-else />

        <div class="flex items-center justify-end gap-3">
          <button
              type="button"
              class="rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm hover:bg-gray-50 disabled:opacity-50"
              :disabled="saving"
              @click="onClose"
          >
            Cancel
          </button>

          <button
              type="button"
              class="rounded-xl bg-gray-900 px-8 py-3 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50"
              :disabled="saving"
              @click="onSaveClick"
          >
            Save
          </button>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import RowField from '@/components/documentManager/parts/RowField.vue'

import vSelect from 'vue-select'
import 'vue-select/dist/vue-select.css'

import { dmOwnersSearch } from '@/api/document-manager'

const props = defineProps({
  open: { type: Boolean, default: false },
  mode: { type: String, default: 'create' },
  saving: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  form: { type: Object, required: true },
  lookups: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['close', 'save', 'delete'])

/**
 * ✅ Doctype -> require_expire logic
 * lookups.doctypes rows look like:
 * { id, name, type_extension, require_expire }
 */
const selectedDoctype = computed(() => {
  const id = Number(props.form?.id_doctype || 0)
  if (!id) return null
  const list = props.lookups?.doctypes || []
  return list.find(d => Number(d?.id) === id) || null
})

const showExpiration = computed(() => {
  return Number(selectedDoctype.value?.require_expire || 0) === 1
})

// If type does NOT require expiration -> clear value + clear expiration error
watch(
    () => [props.form?.id_doctype, props.lookups?.doctypes],
    () => {
      if (!props.open) return
      if (!showExpiration.value) {
        if (props.form) props.form.doc_expiration = ''
        if (props.errors && props.errors.doc_expiration) {
          // do not mutate props.errors object if parent owns it; harmless to ignore
          // parent will revalidate on save anyway
        }
      }
    },
    { deep: false }
)

// owners search (unchanged)
const ownersLoading = ref(false)
const ownerOptions = ref([])
const selectedOwnerOption = ref(null)

const OWNER_LIMIT = 100

const mergedOwnerOptions = computed(() => {
  const map = new Map()
  for (const o of ownerOptions.value || []) {
    if (!o || o.id == null) continue
    map.set(Number(o.id), o)
  }
  if (selectedOwnerOption.value?.id != null) {
    map.set(Number(selectedOwnerOption.value.id), selectedOwnerOption.value)
  }
  return Array.from(map.values())
})

function normalizeRows(rows) {
  return (rows || [])
      .map((x) => ({ id: Number(x.id), name: String(x.name ?? '') }))
      .filter((x) => Number.isFinite(x.id) && x.id > 0)
}

function setSelectedOption(opt) {
  if (!opt || opt.id == null) {
    selectedOwnerOption.value = null
    return
  }
  selectedOwnerOption.value = { id: Number(opt.id), name: String(opt.name ?? '') }
}

function onOwnerSelected(opt) { setSelectedOption(opt) }
function onOwnerCleared() { setSelectedOption(null) }

let reqToken = 0

async function fetchOwnerById(ownerTypeId, ownerId, token) {
  const r = await dmOwnersSearch({ owner_type_id: ownerTypeId, id: ownerId, limit: 1 })
  if (token !== reqToken) return null
  return normalizeRows(r?.rows)[0] || null
}

async function fetchOwnersList(q, token) {
  const r = await dmOwnersSearch({
    owner_type_id: Number(props.form.id_docowner || 0),
    q: q || '',
    limit: OWNER_LIMIT,
  })
  if (token !== reqToken) return
  ownerOptions.value = normalizeRows(r?.rows)
}

async function primeOwners() {
  if (!props.open) return
  const ot = Number(props.form.id_docowner || 0)
  if (!ot) {
    ownerOptions.value = []
    selectedOwnerOption.value = null
    return
  }

  const token = ++reqToken
  ownersLoading.value = true
  try {
    const oid = Number(props.form.id_owner || 0)
    if (oid > 0) {
      const sel = await fetchOwnerById(ot, oid, token)
      if (token !== reqToken) return
      setSelectedOption(sel || { id: oid, name: `#${oid}` })
    } else {
      setSelectedOption(null)
    }

    await fetchOwnersList('', token)
  } finally {
    if (token === reqToken) ownersLoading.value = false
  }
}

let searchTimer = null
function onOwnerSearch(searchText) {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    if (!props.open) return
    const ot = Number(props.form.id_docowner || 0)
    if (!ot) return

    const token = ++reqToken
    ownersLoading.value = true
    try {
      await fetchOwnersList(searchText || '', token)
    } finally {
      if (token === reqToken) ownersLoading.value = false
    }
  }, 250)
}

function onOwnerOpen() {
  if (!props.open) return
  const token = ++reqToken
  ownersLoading.value = true
  fetchOwnersList('', token).finally(() => {
    if (token === reqToken) ownersLoading.value = false
  })
}

// file preview (unchanged)
const fileInputRef = ref(null)
const pickedObjectUrl = ref('')

const previewUrl = computed(() => pickedObjectUrl.value || props.form.file_url || '')
const openUrl = computed(() => previewUrl.value || '')

function cleanupPickedPreview() {
  if (pickedObjectUrl.value) {
    try { URL.revokeObjectURL(pickedObjectUrl.value) } catch (e) {}
    pickedObjectUrl.value = ''
  }
  if (props.form) props.form.file = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

function onPickFile(e) {
  const f = e?.target?.files?.[0] || null

  if (pickedObjectUrl.value) {
    try { URL.revokeObjectURL(pickedObjectUrl.value) } catch (e2) {}
    pickedObjectUrl.value = ''
  }

  props.form.file = f
  if (f) pickedObjectUrl.value = URL.createObjectURL(f)
}

function onClose() {
  cleanupPickedPreview()
  emit('close')
}

function onSaveClick() {
  emit('save')
}

const currentFileName = computed(() => {
  if (props.form?.file && props.form.file.name) return props.form.file.name
  const url = props.form?.file_url || props.form?.path || ''
  if (!url) return ''
  const parts = url.split('/')
  return parts[parts.length - 1] || ''
})

// resize sync (viewer -> modal)
const modalRef = ref(null)
const viewerEl = ref(null)

let ro = null
let lastViewerW = 0
let lastViewerH = 0
let raf = 0

function teardownViewerObserver() {
  if (ro) {
    try { ro.disconnect() } catch (e) {}
    ro = null
  }
  if (raf) {
    cancelAnimationFrame(raf)
    raf = 0
  }
  lastViewerW = 0
  lastViewerH = 0
}

async function setupViewerObserver() {
  teardownViewerObserver()
  await nextTick()

  const el = viewerEl.value
  const modal = modalRef.value
  if (!el || !modal?.getSize || !modal?.setSize) return

  const r0 = el.getBoundingClientRect()
  lastViewerW = r0.width
  lastViewerH = r0.height

  ro = new ResizeObserver(() => {
    if (raf) cancelAnimationFrame(raf)
    raf = requestAnimationFrame(() => {
      const r = el.getBoundingClientRect()
      const newW = r.width
      const newH = r.height

      const dw = newW - lastViewerW
      const dh = newH - lastViewerH
      if (Math.abs(dw) < 1 && Math.abs(dh) < 1) return

      const ms = modal.getSize()
      modal.setSize(ms.width + dw, ms.height + dh)

      lastViewerW = newW
      lastViewerH = newH
    })
  })

  ro.observe(el)
}

watch(
    () => props.open,
    async (v) => {
      if (!v) {
        teardownViewerObserver()
        return
      }
      cleanupPickedPreview()
      await nextTick()
      await primeOwners()
      await setupViewerObserver()
    },
    { flush: 'post' }
)

watch(
    () => previewUrl.value,
    async () => {
      if (!props.open) return
      await setupViewerObserver()
    }
)

watch(
    () => [props.form.id_docowner, props.form.id_owner],
    async () => {
      if (!props.open) return
      await nextTick()
      await primeOwners()
    },
    { flush: 'post' }
)

onBeforeUnmount(() => teardownViewerObserver())
</script>

<style scoped>
.cd-input { width: 300px; }

:deep(.v-select.cd-input) {
  width: 300px !important;
  max-width: 300px !important;
}
:deep(.v-select.cd-input .vs__dropdown-toggle) {
  width: 100%;
  box-sizing: border-box;
}

:deep(.v-select .vs__dropdown-toggle) {
  min-height: 48px;
  padding: 0 1rem;
  border-radius: 0.75rem;
  border: 1px solid #d1d5db;
}

:deep(.v-select .vs__selected),
:deep(.v-select .vs__search),
:deep(.v-select .vs__search::placeholder) {
  line-height: 20px;
  margin: 0;
  padding: 0;
}

:deep(.v-select-error .vs__dropdown-toggle) {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 1px #ef4444 !important;
}

:deep(.vs__dropdown-menu) {
  z-index: 99999 !important;
}
</style>