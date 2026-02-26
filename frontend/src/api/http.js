import axios from 'axios'

export const http = axios.create({
    // IMPORTANT: no /api baseURL
    baseURL: '', // same origin as backend
    withCredentials: true,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
})