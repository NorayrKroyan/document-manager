// resources/js/api/document-manager.js
import { http } from '@/api/http'

export async function dmLookups() {
    const r = await http.get('/document-manager/lookups')
    return r.data
}

export async function dmDocumentsList({ q = '', page = 1, limit = 50, status = 'active' } = {}) {
    const r = await http.get('/document-manager/documents', {
        params: { q, page, limit, status },
    })
    return r.data
}

export async function dmOwnersSearch(params = {}) {
    const r = await http.get('/document-manager/owners/search', { params })
    return r.data
}

export async function dmCreateDocument(payload) {
    const fd = toFormData(payload)
    const r = await http.post('/document-manager/documents', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
    return r.data
}

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

export async function dmRestoreDocument(id_document) {
    const r = await http.post(`/document-manager/documents/${id_document}/restore`)
    return r.data
}

function toFormData(payload) {
    const fd = new FormData()

    Object.entries(payload || {}).forEach(([k, v]) => {
        if (v === undefined) return

        if (k === 'file') {
            if (v instanceof File) fd.append('file', v)
            return
        }

        if (v === null) {
            fd.append(k, '')
            return
        }

        fd.append(k, String(v))
    })

    return fd
}