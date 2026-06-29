import { useEffect, useState } from 'react';
import { getTransacciones, deleteTransaccion } from '../api/api';
import { useApp } from '../context/AppContext';

const formatoMoneda = (valor) =>
  new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);

export default function TransactionList({ refreshKey, onChanged }) {
  const { entorno } = useApp();
  const [mes, setMes] = useState(new Date().toISOString().slice(0, 7));
  const [transacciones, setTransacciones] = useState([]);
  const [cargando, setCargando] = useState(true);

  useEffect(() => {
    setCargando(true);
    getTransacciones(entorno, mes)
      .then((res) => setTransacciones(res.data.data.transacciones))
      .finally(() => setCargando(false));
  }, [entorno, mes, refreshKey]);

  const handleEliminar = async (id) => {
    if (!window.confirm('¿Eliminar este movimiento?')) return;
    await deleteTransaccion(id);
    setTransacciones((prev) => prev.filter((t) => t.id !== id));
    onChanged?.();
  };

  return (
    <div className="transaction-list">
      <div className="list-header">
        <h3>Historial</h3>
        <input type="month" value={mes} onChange={(e) => setMes(e.target.value)} />
      </div>

      {cargando && <p>Cargando movimientos...</p>}

      {!cargando && transacciones.length === 0 && (
        <p className="empty-state">No hay movimientos este mes.</p>
      )}

      {!cargando && transacciones.length > 0 && (
        <table className="transactions-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Categoría</th>
              <th>Descripción</th>
              <th>Monto</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {transacciones.map((t) => (
              <tr key={t.id}>
                <td>{t.fecha}</td>
                <td>{t.categoria_nombre}</td>
                <td>{t.descripcion || '—'}</td>
                <td className={t.tipo === 'ingreso' ? 'monto-ingreso' : 'monto-gasto'}>
                  {t.tipo === 'ingreso' ? '+' : '-'} {formatoMoneda(t.monto)}
                </td>
                <td>
                  <button className="btn-delete" onClick={() => handleEliminar(t.id)}>
                    ✕
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
