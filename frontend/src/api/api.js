import axios from 'axios';

// withCredentials: true es OBLIGATORIO para que el navegador
// envíe y reciba la cookie de sesión (PHPSESSID) en cada petición.
const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
});

// --- Auth ---
export const registerUser = (nombre, email, password) =>
  api.post('/register', { nombre, email, password });

export const loginUser = (email, password) =>
  api.post('/login', { email, password });

export const logoutUser = () => api.post('/logout');

export const getMe = () => api.get('/me');

// --- Categorías ---
export const getCategorias = (entorno) =>
  api.get('/categorias', { params: { entorno } });

export const createCategoria = (nombre, tipo, entorno) =>
  api.post('/categorias', { nombre, tipo, entorno });

export const deleteCategoria = (id) => api.delete(`/categorias/${id}`);

// --- Transacciones ---
export const getTransacciones = (entorno, mes) =>
  api.get('/transacciones', { params: { entorno, mes } });

export const createTransaccion = (payload) =>
  api.post('/transacciones', payload);

export const deleteTransaccion = (id) => api.delete(`/transacciones/${id}`);

// --- Dashboard ---
export const getBalance = (entorno, mes) =>
  api.get('/dashboard/balance', { params: { entorno, mes } });

export const getGastosPorCategoria = (entorno, mes) =>
  api.get('/dashboard/gastos-por-categoria', { params: { entorno, mes } });

export const getTendenciaMensual = (entorno, meses = 6) =>
  api.get('/dashboard/tendencia-mensual', { params: { entorno, meses } });

export default api;
