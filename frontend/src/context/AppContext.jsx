import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { getMe, loginUser, logoutUser, registerUser } from '../api/api';

const AppContext = createContext(null);

export function AppProvider({ children }) {
  const [usuario, setUsuario] = useState(null);
  const [entorno, setEntorno] = useState('personal'); // 'personal' | 'negocio'
  const [cargandoSesion, setCargandoSesion] = useState(true);

  // Al montar la app, preguntamos al backend si ya hay sesión activa
  useEffect(() => {
    getMe()
      .then((res) => setUsuario(res.data.data.usuario))
      .catch(() => setUsuario(null))
      .finally(() => setCargandoSesion(false));
  }, []);

  const login = useCallback(async (email, password) => {
    const res = await loginUser(email, password);
    setUsuario(res.data.data.usuario);
    return res.data;
  }, []);

  const register = useCallback(async (nombre, email, password) => {
    const res = await registerUser(nombre, email, password);
    setUsuario(res.data.data.usuario);
    return res.data;
  }, []);

  const logout = useCallback(async () => {
    await logoutUser();
    setUsuario(null);
  }, []);

  const toggleEntorno = useCallback(() => {
    setEntorno((prev) => (prev === 'personal' ? 'negocio' : 'personal'));
  }, []);

  const value = {
    usuario,
    cargandoSesion,
    login,
    register,
    logout,
    entorno,
    setEntorno,
    toggleEntorno,
  };

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
}

export function useApp() {
  const ctx = useContext(AppContext);
  if (!ctx) {
    throw new Error('useApp debe usarse dentro de un <AppProvider>');
  }
  return ctx;
}
