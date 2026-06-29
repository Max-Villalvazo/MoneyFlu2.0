import { useEffect, useState } from 'react';
import { PieChart, Pie, Cell, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { getGastosPorCategoria } from '../api/api';
import { useApp } from '../context/AppContext';

const formatoMoneda = (valor) =>
  new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);

// Paleta de colores para las rebanadas (independiente del acento de entorno,
// para que cada categoría se distinga claramente entre sí)
const COLORES = [
  '#4f8cff', '#ff9f43', '#2ecc71', '#ff5c5c', '#a55eea',
  '#26de81', '#fd9644', '#45aaf2', '#fc5c65', '#778ca3',
];

export default function GastosPorCategoriaChart({ mes, onMesChange }) {
  const { entorno } = useApp();
  const [datos, setDatos] = useState([]);
  const [cargando, setCargando] = useState(true);

  useEffect(() => {
    setCargando(true);
    getGastosPorCategoria(entorno, mes)
      .then((res) => setDatos(res.data.data.gastos_por_categoria))
      .finally(() => setCargando(false));
  }, [entorno, mes]);

  const total = datos.reduce((acc, d) => acc + d.total, 0);

  return (
    <div className="chart-card full-width">
      <div className="chart-card-header-row">
        <div>
          <h3>¿En qué se va tu dinero?</h3>
          <p className="chart-subtitle">Gastos por categoría</p>
        </div>
        <input type="month" value={mes} onChange={(e) => onMesChange(e.target.value)} />
      </div>

      {cargando ? (
        <p className="empty-state">Cargando gastos por categoría...</p>
      ) : datos.length === 0 ? (
        <p className="empty-state">No hay gastos registrados este mes.</p>
      ) : (
        <>
          <ResponsiveContainer width="100%" height={280}>
            <PieChart>
              <Pie
                data={datos}
                dataKey="total"
                nameKey="categoria"
                cx="50%"
                cy="50%"
                innerRadius={60}
                outerRadius={100}
                paddingAngle={2}
              >
                {datos.map((_, index) => (
                  <Cell key={index} fill={COLORES[index % COLORES.length]} />
                ))}
              </Pie>
              <Tooltip
                formatter={(value) => formatoMoneda(value)}
                contentStyle={{ background: '#1a1d27', border: '1px solid #2a2e3a', borderRadius: 8 }}
                itemStyle={{ color: '#e8e9ed' }}
              />
              <Legend wrapperStyle={{ fontSize: '0.8rem' }} />
            </PieChart>
          </ResponsiveContainer>

          <ul className="chart-legend-list">
            {datos.map((d, i) => (
              <li key={d.categoria}>
                <span className="dot" style={{ background: COLORES[i % COLORES.length] }} />
                <span className="cat-name">{d.categoria}</span>
                <span className="cat-pct">{((d.total / total) * 100).toFixed(0)}%</span>
                <span className="cat-amount">{formatoMoneda(d.total)}</span>
              </li>
            ))}
          </ul>
        </>
      )}
    </div>
  );
}
