import { useEffect, useState } from 'react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  ReferenceLine,
} from 'recharts';
import { getTendenciaMensual } from '../api/api';
import { useApp } from '../context/AppContext';

const formatoMoneda = (valor) =>
  new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);

const nombreMes = (ym) => {
  const [anio, mes] = ym.split('-');
  const fecha = new Date(Number(anio), Number(mes) - 1, 1);
  return fecha.toLocaleDateString('es-MX', { month: 'short', year: '2-digit' });
};

export default function BalanceTrendChart() {
  const { entorno } = useApp();
  const [serie, setSerie] = useState([]);
  const [cargando, setCargando] = useState(true);

  useEffect(() => {
    setCargando(true);
    getTendenciaMensual(entorno, 6)
      .then((res) => {
        const datos = res.data.data.serie.map((d) => ({
          ...d,
          label: nombreMes(d.mes),
        }));
        setSerie(datos);
      })
      .finally(() => setCargando(false));
  }, [entorno]);

  if (cargando) {
    return <div className="chart-card loading">Cargando balance neto...</div>;
  }

  const accentColor = entorno === 'personal' ? '#4f8cff' : '#ff9f43';

  return (
    <div className="chart-card">
      <h3>Balance neto mes a mes</h3>
      <p className="chart-subtitle">Ingresos − Gastos, últimos 6 meses</p>

      <ResponsiveContainer width="100%" height={260}>
        <LineChart data={serie} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke="#2a2e3a" vertical={false} />
          <XAxis dataKey="label" stroke="#8b8fa3" fontSize={12} />
          <YAxis stroke="#8b8fa3" fontSize={12} tickFormatter={(v) => `$${v}`} />
          <ReferenceLine y={0} stroke="#2a2e3a" />
          <Tooltip
            formatter={(value) => formatoMoneda(value)}
            contentStyle={{ background: '#1a1d27', border: '1px solid #2a2e3a', borderRadius: 8 }}
            itemStyle={{ color: '#e8e9ed' }}
            labelStyle={{ color: '#e8e9ed' }}
          />
          <Line
            type="monotone"
            dataKey="balance"
            name="Balance"
            stroke={accentColor}
            strokeWidth={2.5}
            dot={{ r: 4 }}
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}
