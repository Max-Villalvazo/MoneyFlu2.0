import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import EnvSwitch from '../components/EnvSwitch';
import BalanceCard from '../components/BalanceCard';
import TransactionForm from '../components/TransactionForm';
import TransactionList from '../components/TransactionList';

export default function Dashboard() {
  const { usuario, logout, entorno } = useApp();
  const navigate = useNavigate();
  const [refreshKey, setRefreshKey] = useState(0);

  const refrescar = () => setRefreshKey((k) => k + 1);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className={`dashboard-page ${entorno}`}>
      <header className="dashboard-header">
        <div>
          <h1>Hola, {usuario?.nombre}</h1>
          <p className="entorno-label">
            Estás registrando en modo <strong>{entorno === 'personal' ? 'Personal' : 'Negocio'}</strong>
          </p>
        </div>
        <div className="header-actions">
          <EnvSwitch />
          <Link to="/reportes" className="btn-reportes">
            📊 Reportes
          </Link>
          <button className="btn-logout" onClick={handleLogout}>
            Cerrar sesión
          </button>
        </div>
      </header>

      <main className="dashboard-grid">
        <section className="dashboard-col">
          <BalanceCard refreshKey={refreshKey} />
          <TransactionForm onCreated={refrescar} />
        </section>
        <section className="dashboard-col">
          <TransactionList refreshKey={refreshKey} onChanged={refrescar} />
        </section>
      </main>
    </div>
  );
}
