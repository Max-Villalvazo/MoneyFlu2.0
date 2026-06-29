import { useEffect, useState } from 'react';
import { getCategorias, createCategoria, createTransaccion } from '../api/api';
import { useApp } from '../context/AppContext';

const hoyISO = () => new Date().toISOString().slice(0, 10);

export default function TransactionForm({ onCreated }) {
  const { entorno } = useApp();

  const [tipo, setTipo] = useState('gasto');
  const [categorias, setCategorias] = useState([]);
  const [categoriaId, setCategoriaId] = useState('');
  const [monto, setMonto] = useState('');
  const [descripcion, setDescripcion] = useState('');
  const [fecha, setFecha] = useState(hoyISO());
  const [mensaje, setMensaje] = useState(null);
  const [enviando, setEnviando] = useState(false);

  // Para crear categoría nueva al vuelo
  const [mostrarNuevaCategoria, setMostrarNuevaCategoria] = useState(false);
  const [nuevaCategoriaNombre, setNuevaCategoriaNombre] = useState('');

  const cargarCategorias = () => {
    getCategorias(entorno).then((res) => {
      const cats = res.data.data.categorias.filter((c) => c.tipo === tipo);
      setCategorias(cats);
      if (cats.length > 0) {
        setCategoriaId(String(cats[0].id));
      } else {
        setCategoriaId('');
      }
    });
  };

  // Recarga categorías cuando cambia el entorno o el tipo (ingreso/gasto)
  useEffect(() => {
    cargarCategorias();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [entorno, tipo]);

  const handleCrearCategoria = async (e) => {
    e.preventDefault();
    if (!nuevaCategoriaNombre.trim()) return;
    await createCategoria(nuevaCategoriaNombre.trim(), tipo, entorno);
    setNuevaCategoriaNombre('');
    setMostrarNuevaCategoria(false);
    cargarCategorias();
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMensaje(null);

    if (!categoriaId) {
      setMensaje({ tipo: 'error', texto: 'Crea o selecciona una categoría primero.' });
      return;
    }

    setEnviando(true);
    try {
      await createTransaccion({
        categoria_id: Number(categoriaId),
        monto: Number(monto),
        descripcion,
        fecha,
        tipo,
        entorno,
      });
      setMensaje({ tipo: 'success', texto: 'Movimiento registrado.' });
      setMonto('');
      setDescripcion('');
      onCreated?.();
    } catch (err) {
      const texto = err.response?.data?.message || 'Error al registrar el movimiento.';
      setMensaje({ tipo: 'error', texto });
    } finally {
      setEnviando(false);
    }
  };

  return (
    <form className={`transaction-form ${entorno}`} onSubmit={handleSubmit}>
      <h3>Registrar movimiento ({entorno === 'personal' ? 'Personal' : 'Negocio'})</h3>

      <div className="tipo-toggle">
        <button
          type="button"
          className={tipo === 'ingreso' ? 'active' : ''}
          onClick={() => setTipo('ingreso')}
        >
          Ingreso
        </button>
        <button
          type="button"
          className={tipo === 'gasto' ? 'active' : ''}
          onClick={() => setTipo('gasto')}
        >
          Gasto
        </button>
      </div>

      <label>
        Categoría
        <div className="categoria-row">
          <select value={categoriaId} onChange={(e) => setCategoriaId(e.target.value)}>
            {categorias.length === 0 && <option value="">Sin categorías</option>}
            {categorias.map((c) => (
              <option key={c.id} value={c.id}>
                {c.nombre}
              </option>
            ))}
          </select>
          <button
            type="button"
            className="btn-link"
            onClick={() => setMostrarNuevaCategoria((v) => !v)}
          >
            + Nueva
          </button>
        </div>
      </label>

      {mostrarNuevaCategoria && (
        <div className="nueva-categoria-row">
          <input
            type="text"
            placeholder="Nombre de la categoría"
            value={nuevaCategoriaNombre}
            onChange={(e) => setNuevaCategoriaNombre(e.target.value)}
          />
          <button type="button" onClick={handleCrearCategoria}>
            Guardar
          </button>
        </div>
      )}

      <label>
        Monto
        <input
          type="number"
          step="0.01"
          min="0.01"
          placeholder="0.00"
          value={monto}
          onChange={(e) => setMonto(e.target.value)}
          required
        />
      </label>

      <label>
        Descripción
        <input
          type="text"
          placeholder="Opcional"
          value={descripcion}
          onChange={(e) => setDescripcion(e.target.value)}
        />
      </label>

      <label>
        Fecha
        <input type="date" value={fecha} onChange={(e) => setFecha(e.target.value)} required />
      </label>

      {mensaje && <p className={`form-msg ${mensaje.tipo}`}>{mensaje.texto}</p>}

      <button type="submit" className="btn-submit" disabled={enviando}>
        {enviando ? 'Guardando...' : 'Guardar movimiento'}
      </button>
    </form>
  );
}
