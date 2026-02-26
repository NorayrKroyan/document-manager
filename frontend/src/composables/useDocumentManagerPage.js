import { ref, computed } from 'vue'
import { dmLookups, dmDocumentsList } from '@/api/document-manager'

export function useDocumentManagerPage() {
    const loading = ref(false)
    const err = ref('')

    const q = ref('')
    const list = ref([])

    const page = ref(1)
    const limit = ref(50)
    const total = ref(0)

    const lookups = ref({
        doctypes: [],
        docowners: [],
    })

    const totalPages = computed(() => {
        const t = Number(total.value || 0)
        const l = Number(limit.value || 1)
        return Math.max(1, Math.ceil(t / l))
    })

    const canNext = computed(() => page.value < totalPages.value)

    async function loadLookups() {
        const r = await dmLookups()
        if (!r?.ok) throw new Error('Lookups failed')
        lookups.value = {
            doctypes: r.doctypes || [],
            docowners: r.docowners || [],
        }
    }

    async function reloadList() {
        loading.value = true
        err.value = ''
        try {
            const r = await dmDocumentsList({ q: q.value, page: page.value, limit: limit.value })
            if (!r?.ok) throw new Error('List failed')
            list.value = r.rows || []
            total.value = Number(r.total || 0)
        } catch (e) {
            err.value = e?.message || 'Failed to load'
        } finally {
            loading.value = false
        }
    }

    async function boot() {
        await loadLookups()
        await reloadList()
    }

    return {
        loading, err,
        q, list,
        page, limit, total, totalPages, canNext,
        lookups,
        boot, reloadList,
    }
}