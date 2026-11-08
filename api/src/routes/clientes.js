const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);

router.get('/', async (req, res, next) => {
  try {
    const { rows } = await db.query(
      `SELECT id, nombre, nit, telefono, correo, vendedor_id, created_at, updated_at
       FROM clientes WHERE vendedor_id = $1 ORDER BY nombre`,
      [req.user.id],
    );
    res.json({ datos: rows });
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
    const { nombre, nit = null, telefono, correo = null } = req.body || {};
    if (!nombre || !/^\d{8}$/.test(String(telefono || ''))) {
      return res.status(422).json({ mensaje: 'Nombre y teléfono de 8 dígitos son obligatorios.' });
    }
    const connection = await db.connect();
    try {
      await connection.query('BEGIN');
      // Laravel locks the same seller row before assigning a client.
      if (await carteraLlena(connection, req.user.id)) {
        await connection.query('ROLLBACK');
        return res.status(409).json({ mensaje: 'Su cartera ya tiene 10 clientes, el máximo permitido.' });
      }

      const { rows } = await connection.query(
        'INSERT INTO clientes (vendedor_id, nombre, nit, telefono, correo, created_at, updated_at) VALUES ($1, $2, $3, $4, $5, NOW(), NOW()) RETURNING id, nombre, nit, telefono, correo, vendedor_id',
        [req.user.id, nombre, nit, telefono, correo],
      );
      await connection.query('COMMIT');
      res.status(201).json({ dato: rows[0] });
    } catch (error) {
      await connection.query('ROLLBACK');
      throw error;
    } finally {
      connection.release();
    }
  } catch (error) {
    if (error.code === '23505') return res.status(409).json({ mensaje: 'NIT o teléfono ya registrado.' });
    next(error);
  }
});

module.exports = router;
