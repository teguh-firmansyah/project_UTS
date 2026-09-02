import api from './api'

export async function login(credentials) {
  // WAJIB dipanggil dulu sebelum login, untuk dapat CSRF cookie
  await api.get('/sanctum/csrf-cookie')
  const response = await api.post('/api/login', credentials)
  return response.data
}

export async function register(payload) {
  await api.get('/sanctum/csrf-cookie')
  const response = await api.post('/api/register', payload)
  return response.data
}

export async function logout() {
  await api.post('/api/logout')
}

export async function getCurrentUser() {
  const response = await api.get('/api/me')
  return response.data
}