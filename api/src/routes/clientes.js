const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);


const clientFields = 'id, nombre, nit, telefono, correo';
const publicClient = row => ({ ...row, nit: row.nit ?? 'CF' });
const validId = id => /^[1-9]\d{0,18}$/.test(id) && BigInt(id) <= 9223372036854775807n;

function validateClient(body, partial = false) {
  const data = {};
  const errores = {};
  const fields = ['nombre', 'nit', 'telefono', 'correo'];
  for (const field of fields) {
    if (partial && !Object.hasOwn(body, field)) continue;
    const value = body[field];
    if (field === 'nombre') {
      if (typeof value !== 'string' || !value.trim() || value.trim().length > 150) errores.nombre = 'Ingrese un nombre válido.';
      else data.nombre = value.trim();
    } else if (field === 'nit') {
      const nit = typeof value === 'string' ? value.toUpperCase().replace(/[\s-]/g, '').replace(/^0+(?=\d)/, '') : '';
      if (value == null || value === '' || nit === 'CF') data.nit = null;
      else if (/^\d{1,11}[0-9K]$/.test(nit)) {
        const sum = [...nit.slice(0, -1)].reverse().reduce((total, digit, i) => total + Number(digit) * (i + 2), 0);
        const check = (11 - sum % 11) % 11;
        if (nit.at(-1) === (check === 10 ? 'K' : String(check))) data.nit = nit;
        else errores.nit = 'Escriba un NIT válido o CF.';
      } else errores.nit = 'Escriba un NIT válido o CF.';
    } else if (field === 'telefono') {
      const clean = typeof value === 'string' ? value.replace(/[\s.()-]/g, '') : '';
      const telefono = clean.replace(/^(?:\+502|502)(?=\d{8}$)/, '');
      if (!/^[2-9]\d{7}$/.test(telefono)) errores.telefono = 'Ingrese un teléfono de Guatemala de 8 dígitos.';
      else data.telefono = telefono;
    } else if (value == null || (typeof value === 'string' && !value.trim())) data.correo = null;
    else if (typeof value !== 'string' || value.trim().length > 150 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())) {
      errores.correo = 'Ingrese un correo válido.';
    } else data.correo = value.trim();
  }
  return { data, errores };
}

router.get('/', async (req, res, next) => {
  const q = req.query.q ?? '';
  if (typeof q !== 'string') return res.status(400).json({ error: 'Búsqueda inválida.' });
  try {
    const { rows } = await db.query(
      `SELECT ${clientFields} FROM clientes WHERE vendedor_id = $1
       AND (strpos(lower(nombre), lower($2)) > 0 OR strpos(lower(COALESCE(nit, 'CF')), lower($2)) > 0)
       ORDER BY nombre`, [req.user.id, q.trim()],
    );
    res.json(rows.map(publicClient));
  } catch (error) { next(error); }
});
router.get('/disponibles', async (_req, res, next) => {
  try {
    const { rows } = await db.query('SELECT id, nombre FROM clientes WHERE vendedor_id IS NULL ORDER BY nombre');
    res.json(rows);
  } catch (error) { next(error); }
});

router.post('/:id/tomar', async (req, res, next) => {
  const { id } = req.params;
  if (!/^[1-9]\d*$/.test(id) || BigInt(id) > 9223372036854775807n) {
    return res.status(404).json({ error: 'Cliente no encontrado.' });
  }

  try {
    const connection = await db.connect();
    try {
      await connection.query('BEGIN');
      // Lock the client first, matching the order used by the Laravel panel.
      const { rows: [cliente] } = await connection.query(
        'SELECT vendedor_id FROM clientes WHERE id = $1 FOR UPDATE', [id],
      );
      if (!cliente || cliente.vendedor_id !== null) {
        await connection.query('ROLLBACK');
        return cliente
          ? res.status(409).json({ error: 'Este cliente ya no está disponible.' })
          : res.status(404).json({ error: 'Cliente no encontrado.' });
      }

      if (await carteraLlena(connection, req.user.id)) {
        await connection.query('ROLLBACK');
        return res.status(409).json({ error: 'Su cartera ya tiene 10 clientes, el máximo permitido.' });
      }

      const { rows: [assigned] } = await connection.query(
        'UPDATE clientes SET vendedor_id = $1, updated_at = NOW() WHERE id = $2 RETURNING id, nombre, nit, telefono, correo',
        [req.user.id, id],
      );
      await connection.query('COMMIT');
      res.json({ ...assigned, nit: assigned.nit ?? 'CF' });
    } catch (error) {
      await connection.query('ROLLBACK');
      throw error;
    } finally {
      connection.release();
    }
  } catch (error) { next(error); }
});

async function carteraLlena(connection, vendedorId) {
  await connection.query('SELECT id FROM users WHERE id = $1 FOR UPDATE', [vendedorId]);
  const { rows: [cartera] } = await connection.query(
    'SELECT COUNT(*) AS total FROM clientes WHERE vendedor_id = $1', [vendedorId],
  );
  return Number(cartera.total) >= 10;
}

router.post('/', async (req, res, next) => {
  try {
    if (!req.body || typeof req.body !== 'object' || Array.isArray(req.body)) {
      return res.status(400).json({ error: 'Envíe un objeto JSON.' });
    }
    const { data, errores } = validateClient(req.body);
    if (Object.keys(errores).length) return res.status(422).json({ errores });
    const { nombre, nit, telefono, correo } = data;
    const connection = await db.connect();
    try {
      await connection.query('BEGIN');
      // Laravel locks the same seller row before assigning a client.
      if (await carteraLlena(connection, req.user.id)) {
        await connection.query('ROLLBACK');
        return res.status(409).json({ error: 'Su cartera ya tiene 10 clientes, el máximo permitido.' });
      }

      const { rows } = await connection.query(
        'INSERT INTO clientes (vendedor_id, nombre, nit, telefono, correo, created_at, updated_at) VALUES ($1, $2, $3, $4, $5, NOW(), NOW()) RETURNING id, nombre, nit, telefono, correo',
        [req.user.id, nombre, nit, telefono, correo],
      );
      await connection.query('COMMIT');
      res.status(201).json(publicClient(rows[0]));
    } catch (error) {
      await connection.query('ROLLBACK');
      throw error;
    } finally {
      connection.release();
    }
  } catch (error) {
    if (error.code === '23505') return res.status(409).json({ error: 'NIT o teléfono ya registrado.' });
    next(error);
  }
});


router.get('/:id', async (req, res, next) => {
  if (!validId(req.params.id)) return res.status(404).json({ error: 'Cliente no encontrado.' });
  try {
    const { rows: [item] } = await db.query(
      `SELECT ${clientFields} FROM clientes WHERE id = $1 AND vendedor_id = $2`, [req.params.id, req.user.id],
    );
    if (!item) return res.status(404).json({ error: 'Cliente no encontrado.' });
    res.json(publicClient(item));
  } catch (error) { next(error); }
});

router.patch('/:id', async (req, res, next) => {
  if (!validId(req.params.id)) return res.status(404).json({ error: 'Cliente no encontrado.' });
  if (!req.body || typeof req.body !== 'object' || Array.isArray(req.body)) {
    return res.status(400).json({ error: 'Envíe un objeto JSON.' });
  }
  const { data, errores } = validateClient(req.body, true);
  try {
    const { rows: [item] } = await db.query(
      `SELECT ${clientFields} FROM clientes WHERE id = $1 AND vendedor_id = $2`, [req.params.id, req.user.id],
    );
    if (!item) return res.status(404).json({ error: 'Cliente no encontrado.' });
    if (Object.keys(errores).length) return res.status(422).json({ errores });
    const fields = Object.keys(data);
    if (!fields.length) return res.json(publicClient(item));
    const assignments = fields.map((field, i) => field + ' = $' + (i + 3)).join(', ');
    const { rows: [updated] } = await db.query(
      `UPDATE clientes SET ${assignments}, updated_at = NOW()
       WHERE id = $1 AND vendedor_id = $2 RETURNING ${clientFields}`,
      [req.params.id, req.user.id, ...Object.values(data)],
    );
    if (!updated) return res.status(404).json({ error: 'Cliente no encontrado.' });
    res.json(publicClient(updated));
  } catch (error) {
    if (error.code === '23505') return res.status(409).json({ error: 'NIT o teléfono ya registrado.' });
    next(error);
  }
});

module.exports = router;
