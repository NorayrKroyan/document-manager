import { http } from '@/api/http'

export async function dmLookups() {
    const r = await http.get('/document-manager/lookups')
    return r.data
}

export async function dmDocumentsList({ q = '', page = 1, limit = 50 } = {}) {
    const r = await http.get('/document-manager/documents', { params: { q, page, limit } })
    return r.data
}

export async function dmOwnersSearch(params = {}) {
    const r = await http.get('/document-manager/owners/search', { params })
    return r.data
}

// Create: multipart with file required by backend
export async function dmCreateDocument(payload) {
    const fd = toFormData(payload)
    const r = await http.post('/document-manager/documents', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
    return r.data
}

// Update: multipart optional file
export async function dmUpdateDocument(id_document, payload) {
    const fd = toFormData(payload)
    const r = await http.post(`/document-manager/documents/${id_document}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
    return r.data
}

export async function dmDeleteDocument(id_document) {
    const r = await http.post(`/document-manager/documents/${id_document}/delete`)
    return r.data
}

function toFormData(payload) {
    const fd = new FormData()
    Object.entries(payload || {}).forEach(([k, v]) => {
        if (v === undefined) return
        if (v === null) {
            fd.append(k, '')
            return
        }
        if (k === 'file') {
            if (v instanceof File) fd.append('file', v)
            return
        }
        fd.append(k, String(v))
    })
    return fd
}