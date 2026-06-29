import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import EnvSwitch from '../components/EnvSwitch';
import GastosPorCategoriaChart from '../components/GastosPorCategoriaChart';
import TendenciaMensualChart from '../components/TendenciaMensualChart';
import BalanceTrendChart from '../components/BalanceTrendChart';

export default function Reportes() {
  const { entorno } = useApp();
  const [mes, setMes] = useState(new Date().toISOString().slice(0, 7));

  return (
    <div className={`dashboard-page ${entorno}`}>
      <header className="dashboard-header">
        <div>
          <h1>Reportes</h1>
          <p className="entorno-label">
            Viendo gráficas de modo <strong>{entorno === 'personal' ? 'Personal' : 'Negocio'}</strong>
          </p>
        </div>
        <div className="header-actions">
          <EnvSwitch />
          <Link to="/" className="btn-reportes">
            ← Volver al dashboard
          </Link>
        </div>
      </header>

      <main className="reportes-grid">
        <GastosPorCategoriaChart mes={mes} onMesChange={setMes} />
        <TendenciaMensualChart />
        <BalanceTrendChart />
      </main>
    </div>
  );
}
