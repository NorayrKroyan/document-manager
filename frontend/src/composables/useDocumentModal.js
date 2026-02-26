import { ref, reactive } from 'vue'
import { dmCreateDocument, dmUpdateDocument, dmDeleteDocument } from '@/api/document-manager'

function emptyForm() {
    return reactive({
        id_document: null,

        id_doctype: null,
        doc_name: '',
        doc_description: '',
        id_docowner: null,
        id_owner: null,
        doc_expiration: '',

        // for preview
        path: '',
        file_url: '',

        // upload
        file: null,
    })
}

function toIntOrNull(v) {
    const n = Number(v)
    return Number.isFinite(n) && n > 0 ? n : null
}

export function useDocumentModal() {
    const open = ref(false)
    const mode = ref('create') // create|edit
    const saving = ref(false)
    const deleting = ref(false)
    const errors = ref({})

    const form = emptyForm()

    function openCreate() {
        mode.value = 'create'
        errors.value = {}
        resetForm()
        open.value = true
    }

    function openEdit(row) {
        mode.value = 'edit'
        errors.value = {}
        resetForm()

        form.id_document = toIntOrNull(row?.id_document)

        form.id_doctype = toIntOrNull(row?.id_doctype)
        form.doc_name = row?.doc_name || ''
        form.doc_description = row?.doc_description || ''

        // ✅ IMPORTANT: keep numeric types stable for vue-select
        form.id_docowner = toIntOrNull(row?.id_docowner)
        form.id_owner = toIntOrNull(row?.id_owner)

        form.doc_expiration = row?.doc_expiration || ''

        form.path = row?.path || ''
        form.file_url = row?.file_url || ''
        form.file = null

        open.value = true
    }

    function close() {
        open.value = false
    }

    function resetForm() {
        form.id_document = null
        form.id_doctype = null
        form.doc_name = ''
        form.doc_description = ''
        form.id_docowner = null
        form.id_owner = null
        form.doc_expiration = ''
        form.path = ''
        form.file_url = ''
        form.file = null
    }

    function setFieldErrorsFromAxios(err) {
        const e = err?.response?.data?.errors
        if (e && typeof e === 'object') {
            const out = {}
            Object.keys(e).forEach(k => {
                out[k] = Array.isArray(e[k]) ? e[k][0] : String(e[k])
            })
            errors.value = out
            return true
        }
        return false
    }

    async function save() {
        saving.value = true
        errors.value = {}
        try {
            const payload = {
                id_doctype: form.id_doctype,
                doc_name: form.doc_name,
                doc_description: form.doc_description,
                id_docowner: form.id_docowner,
                id_owner: form.id_owner,
                doc_expiration: form.doc_expiration || null,
                file: form.file, // File or null
            }

            let r
            if (mode.value === 'edit' && form.id_document) {
                r = await dmUpdateDocument(form.id_document, payload)
            } else {
                r = await dmCreateDocument(payload)
            }

            if (!r?.ok) {
                if (r?.errors) errors.value = r.errors
                return r
            }

            open.value = false
            return r
        } catch (err) {
            if (!setFieldErrorsFromAxios(err)) {
                errors.value = { _general: err?.message || 'Save failed' }
            }
            return { ok: false }
        } finally {
            saving.value = false
        }
    }

    async function del() {
        if (!form.id_document) return { ok: false }
        deleting.value = true
        errors.value = {}
        try {
            const r = await dmDeleteDocument(form.id_document)
            open.value = false
            return r
        } catch (err) {
            errors.value = { _general: err?.message || 'Delete failed' }
            return { ok: false }
        } finally {
            deleting.value = false
        }
    }

    return {
        open, mode, saving, deleting, errors, form,
        openCreate, openEdit, close, save, del,
    }
}