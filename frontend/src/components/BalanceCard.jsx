import { useEffect, useState } from 'react';
import { getBalance } from '../api/api';
import { useApp } from '../context/AppContext';

const formatoMoneda = (valor) =>
  new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);

export default function BalanceCard({ refreshKey }) {
  const { entorno } = useApp();
  const [balance, setBalance] = useState(null);
  const [cargando, setCargando] = useState(true);

  const mesActual = new Date().toISOString().slice(0, 7); // YYYY-MM

  useEffect(() => {
    setCargando(true);
    getBalance(entorno, mesActual)
      .then((res) => setBalance(res.data.data))
      .catch(() => setBalance(null))
      .finally(() => setCargando(false));
  }, [entorno, refreshKey]);

  if (cargando) {
    return <div className="balance-card loading">Calculando balance...</div>;
  }

  if (!balance) {
    return <div className="balance-card error">No se pudo cargar el balance.</div>;
  }

  const esPositivo = balance.balance >= 0;

  return (
    <div className={`balance-card ${entorno}`}>
      <div className="balance-row">
        <span className="balance-label">Ingresos</span>
        <span className="balance-value ingreso">{formatoMoneda(balance.ingresos)}</span>
      </div>
      <div className="balance-row">
        <span className="balance-label">Gastos</span>
        <span className="balance-value gasto">{formatoMoneda(balance.gastos)}</span>
      </div>
      <hr />
      <div className="balance-row total">
        <span className="balance-label">Balance del mes</span>
        <span className={`balance-value ${esPositivo ? 'positivo' : 'negativo'}`}>
          {formatoMoneda(balance.balance)}
        </span>
      </div>
    </div>
  );
}
