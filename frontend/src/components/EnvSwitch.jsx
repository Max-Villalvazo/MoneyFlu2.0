import { useApp } from '../context/AppContext';

export default function EnvSwitch() {
  const { entorno, setEntorno } = useApp();

  return (
    <div className="env-switch">
      <button
        className={`env-btn ${entorno === 'personal' ? 'active personal' : ''}`}
        onClick={() => setEntorno('personal')}
        type="button"
      >
        Personal
      </button>
      <button
        className={`env-btn ${entorno === 'negocio' ? 'active negocio' : ''}`}
        onClick={() => setEntorno('negocio')}
        type="button"
      >
        Negocio
      </button>
    </div>
  );
}
