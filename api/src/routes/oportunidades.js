const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const STAGES = ['prospecto', 'calificado', 'propuesta', 'negociacion', 'ganada', 'perdida'];
const router = express.Router();
router.use(requireVendor);

async function canWinCustomOpportunity(clienteId, titulo) {
  if (!String(titulo).toLowerCase().includes('a medida')) return true;
  const { rows } = await db.query(
    `SELECT a.id FROM actividades a
     WHERE a.cliente_id = $1 AND a.tipo = 'reunion' AND a.completada = TRUE
     AND (LOWER(a.descripcion) LIKE '%medicion%' OR LOWER(a.descripcion) LIKE '%medición%') LIMIT 1`,
    [clienteId],
  );
  return Boolean(rows[0]);
}

router.get('/', async (req, res, next) => {
  try {
    const { rows } = await db.query(
      `SELECT o.id, o.cliente_id, c.nombre AS cliente, o.titulo, o.etapa, o.motivo_perdida,
              o.fecha_cierre, o.created_at, o.updated_at
       FROM oportunidades o JOIN clientes c ON c.id = o.cliente_id
       WHERE o.vendedor_id = $1 ORDER BY o.updated_at DESC`,
      [req.user.id],
    );
    res.json({ datos: rows });
  } catch (error) { next(error); }
});


const CLOSED = ['ganada', 'perdida'];

function validId(value) {
  return /^[1-9]\d{0,18}$/.test(String(value)) && BigInt(value) <= 9223372036854775807n;
}

function fail(status, message) {
  throw Object.assign(new Error(message), { status });
}

function respondError(error, res, next) {
  if (error.status) return res.status(error.status).json({ error: error.message });
  next(error);
}

async function transaction(work) {
  const connection = await db.connect();
  try {
    await connection.query('BEGIN');
    const result = await work(connection);
    await connection.query('COMMIT');
    return result;
  } catch (error) {
    await connection.query('ROLLBACK');
    throw error;
  } finally {
    connection.release();
  }
}

async function findOpportunity(connection, id, sellerId, lock = false) {
  if (!validId(id)) fail(404, 'Oportunidad no encontrada.');
  const { rows: [item] } = await connection.query(
    'SELECT * FROM oportunidades WHERE id = $1 AND vendedor_id = $2' + (lock ? ' FOR UPDATE' : ''),
    [id, sellerId],
  );
  if (!item) fail(404, 'Oportunidad no encontrada.');
  return item;
}

async function detail(connection, item) {
  const { rows: productos } = await connection.query(
    `SELECT l.id, l.producto_id, p.nombre, l.cantidad, l.precio_unitario::float8 AS precio_unitario,
            (l.cantidad * l.precio_unitario)::float8 AS subtotal
     FROM oportunidad_producto l JOIN productos p ON p.id = l.producto_id
     WHERE l.oportunidad_id = $1 ORDER BY l.id`, [item.id],
  );
  const { rows: [summary] } = await connection.query(
    `SELECT c.id, c.nombre, COALESCE(SUM(l.cantidad * l.precio_unitario), 0)::float8 AS total
     FROM clientes c LEFT JOIN oportunidad_producto l ON l.oportunidad_id = $1
     WHERE c.id = $2 GROUP BY c.id`, [item.id, item.cliente_id],
  );
  const { rows: historial } = await connection.query(
    `SELECT h.etapa_anterior, h.etapa_nueva, u.name AS usuario, h.created_at AS fecha
     FROM historial_etapas h JOIN users u ON u.id = h.usuario_id
     WHERE h.oportunidad_id = $1 ORDER BY h.id`, [item.id],
  );
  return {
    id: item.id, titulo: item.titulo, etapa: item.etapa,
    fecha_cierre: item.fecha_cierre, motivo_perdida: item.motivo_perdida,
    cliente: { id: summary.id, nombre: summary.nombre },
    probabilidad: [10, 25, 50, 75, 100, 0][STAGES.indexOf(item.etapa)],
    total: summary.total, productos, historial,
  };
}

router.get('/:id', async (req, res, next) => {
  try {
    const item = await findOpportunity(db, req.params.id, req.user.id);
    res.json(await detail(db, item));
  } catch (error) { respondError(error, res, next); }
});

function productChange(method) {
  return async (req, res, next) => {
    try {
      const result = await transaction(async connection => {
        const item = await findOpportunity(connection, req.params.id, req.user.id, true);
        if (CLOSED.includes(item.etapa)) fail(409, 'La oportunidad ya está cerrada.');
        const { producto_id, cantidad } = req.body || {};
        if (method !== 'delete' && (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > 999)) {
          fail(422, 'La cantidad debe ser un entero entre 1 y 999.');
        }
        if (method === 'post') {
          if (!validId(producto_id)) fail(422, 'Producto inválido.');
          const { rowCount } = await connection.query(
            `INSERT INTO oportunidad_producto (oportunidad_id, producto_id, cantidad, precio_unitario, created_at, updated_at)
             SELECT $1, id, $3, precio, NOW(), NOW() FROM productos WHERE id = $2 AND activo = TRUE
             ON CONFLICT (oportunidad_id, producto_id) DO NOTHING`, [item.id, producto_id, cantidad],
          );
          if (!rowCount) {
            const { rowCount: existing } = await connection.query(
              'SELECT 1 FROM oportunidad_producto WHERE oportunidad_id = $1 AND producto_id = $2', [item.id, producto_id],
            );
            if (existing) fail(409, 'El producto ya está en la oportunidad.');
            fail(422, 'El producto no está disponible.');
          }
        } else {
          const { lineaId } = req.params;
          if (!validId(lineaId)) fail(404, 'Línea no encontrada.');
          const { rowCount } = method === 'delete'
            ? await connection.query('DELETE FROM oportunidad_producto WHERE oportunidad_id = $1 AND id = $2', [item.id, lineaId])
            : await connection.query(
              'UPDATE oportunidad_producto SET cantidad = $3, updated_at = NOW() WHERE oportunidad_id = $1 AND id = $2',
              [item.id, lineaId, cantidad],
            );
          if (!rowCount) fail(404, 'Línea no encontrada.');
          if (method === 'delete') return null;
        }
        return detail(connection, item);
      });
      if (method === 'delete') return res.status(204).end();
      res.status(method === 'post' ? 201 : 200).json(result);
    } catch (error) { respondError(error, res, next); }
  };
}

router.post('/:id/productos', productChange('post'));
router.patch('/:id/productos/:lineaId', productChange('patch'));
router.delete('/:id/productos/:lineaId', productChange('delete'));

router.post('/', async (req, res, next) => {
  try {
    const { cliente_id, titulo, etapa = 'prospecto', motivo_perdida = null, fecha_cierre = null } = req.body || {};
    if (!cliente_id || !titulo || !STAGES.includes(etapa)) return res.status(422).json({ mensaje: 'Cliente, título y etapa válida son obligatorios.' });
    const { rows: clients } = await db.query('SELECT id FROM clientes WHERE id = $1 AND (vendedor_id = $2 OR vendedor_id IS NULL)', [cliente_id, req.user.id]);
    if (!clients[0]) return res.status(403).json({ mensaje: 'El cliente no pertenece a tu cartera.' });
    if (etapa === 'perdida' && !motivo_perdida) return res.status(422).json({ mensaje: 'Una oportunidad perdida necesita motivo.' });
    if (['ganada', 'perdida'].includes(etapa) && !fecha_cierre) return res.status(422).json({ mensaje: 'Una oportunidad cerrada necesita fecha de cierre.' });
    if (etapa === 'ganada' && !(await canWinCustomOpportunity(cliente_id, titulo))) return res.status(422).json({ mensaje: 'Una oportunidad a medida necesita una reunión de medición completada.' });

    const client = await db.connect();
    try {
      await client.query('BEGIN');
      const { rows: inserted } = await client.query(
        `INSERT INTO oportunidades (cliente_id, vendedor_id, titulo, etapa, motivo_perdida, fecha_cierre, created_at, updated_at)
         VALUES ($1, $2, $3, $4, $5, $6, NOW(), NOW()) RETURNING id`,
        [cliente_id, req.user.id, titulo, etapa, etapa === 'perdida' ? motivo_perdida : null, ['ganada', 'perdida'].includes(etapa) ? fecha_cierre : null],
      );
      await client.query('INSERT INTO historial_etapas (oportunidad_id, usuario_id, etapa_anterior, etapa_nueva, created_at) VALUES ($1, $2, NULL, $3, NOW())', [inserted[0].id, req.user.id, etapa]);
      await client.query('COMMIT');
      res.status(201).json({ id: inserted[0].id, mensaje: 'Oportunidad creada.' });
    } catch (error) { await client.query('ROLLBACK'); throw error; } finally { client.release(); }
  } catch (error) { next(error); }
});

router.patch('/:id/etapa', async (req, res, next) => {
  try {
    const { etapa, motivo_perdida = null, fecha_cierre = null } = req.body || {};
    if (!STAGES.includes(etapa)) return res.status(422).json({ mensaje: 'Etapa inválida.' });
    const { rows } = await db.query('SELECT * FROM oportunidades WHERE id = $1 AND vendedor_id = $2', [req.params.id, req.user.id]);
    const opportunity = rows[0];
    if (!opportunity) return res.status(404).json({ mensaje: 'Oportunidad no encontrada.' });
    if (etapa === 'perdida' && !motivo_perdida) return res.status(422).json({ mensaje: 'Una oportunidad perdida necesita motivo.' });
    if (['ganada', 'perdida'].includes(etapa) && !fecha_cierre) return res.status(422).json({ mensaje: 'Una oportunidad cerrada necesita fecha de cierre.' });
    if (etapa === 'ganada' && !(await canWinCustomOpportunity(opportunity.cliente_id, opportunity.titulo))) return res.status(422).json({ mensaje: 'Una oportunidad a medida necesita una reunión de medición completada.' });
    await db.query('UPDATE oportunidades SET etapa = $1, motivo_perdida = $2, fecha_cierre = $3, updated_at = NOW() WHERE id = $4', [etapa, etapa === 'perdida' ? motivo_perdida : null, ['ganada', 'perdida'].includes(etapa) ? fecha_cierre : null, opportunity.id]);
    await db.query('INSERT INTO historial_etapas (oportunidad_id, usuario_id, etapa_anterior, etapa_nueva, created_at) VALUES ($1, $2, $3, $4, NOW())', [opportunity.id, req.user.id, opportunity.etapa, etapa]);
    res.json({ mensaje: 'Etapa actualizada.' });
  } catch (error) { next(error); }
});

module.exports = router;
