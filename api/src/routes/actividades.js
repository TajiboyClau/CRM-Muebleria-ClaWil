const express = require('express');
const db = require('../db');
const { requireVendor } = require('../middleware/auth');

const router = express.Router();
router.use(requireVendor);
const columns = `a.id, json_build_object('id', c.id, 'nombre', c.nombre) AS cliente,
  a.tipo, a.descripcion, a.vence_en, a.completada,
  (NOT a.completada AND COALESCE(a.vence_en < NOW() AT TIME ZONE 'UTC', FALSE)) AS vencida`;
const validId = id => /^[1-9]\d{0,18}$/.test(id) && BigInt(id) <= 9223372036854775807n;

router.get('/', async (req, res, next) => {
  const { estado } = req.query;
  if (estado !== undefined && !['pendiente', 'completada'].includes(estado)) {
    return res.status(400).json({ error: 'Estado de actividad inválido.' });
  }
  try {
    const { rows } = await db.query(
      `SELECT ${columns} FROM actividades a JOIN clientes c ON c.id = a.cliente_id
       WHERE c.vendedor_id = $1 AND ($2::boolean IS NULL OR a.completada = $2)
       ORDER BY a.completada, a.vence_en`,
      [req.user.id, estado === undefined ? null : estado === 'completada'],
    );
    res.json(rows);
  } catch (error) { next(error); }
});

async function createActivity(req, res, next) {
  const { id } = req.params;
  const { tipo, descripcion, vence_en } = req.body || {};
  const errores = {};
  if (!['llamada', 'reunion', 'tarea'].includes(tipo)) errores.tipo = 'Seleccione un tipo válido.';
  if (typeof descripcion !== 'string' || !descripcion.trim() || descripcion.trim().length > 500) {
    errores.descripcion = 'Escriba una descripción de 1 a 500 caracteres.';
  }
  if (vence_en != null && (typeof vence_en !== 'string'
    || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:Z|[+-]\d{2}:\d{2})$/.test(vence_en)
    || !Number.isFinite(Date.parse(vence_en)))) {
    errores.vence_en = 'Indique una fecha ISO 8601 con zona horaria.';
  }
  if (Object.keys(errores).length) return res.status(422).json({ errores });
  if (!validId(id)) return res.status(404).json({ error: 'Cliente no encontrado.' });
  try {
    const { rows: [activity] } = await db.query(
      `WITH assigned AS (
         SELECT id, nombre FROM clientes WHERE id = $1 AND vendedor_id = $2 FOR UPDATE
       ), a AS (
         INSERT INTO actividades (cliente_id, vendedor_id, tipo, descripcion, vence_en, completada, created_at, updated_at)
         SELECT id, $2, $3, $4, $5::timestamptz AT TIME ZONE 'UTC', FALSE, NOW(), NOW() FROM assigned RETURNING *
       )
       SELECT ${columns} FROM a JOIN assigned c ON c.id = a.cliente_id`,
      [id, req.user.id, tipo, descripcion.trim(), vence_en ?? null],
    );
    if (!activity) return res.status(404).json({ error: 'Cliente no encontrado.' });
    res.status(201).json(activity);
  } catch (error) { next(error); }
}

router.patch('/:id', async (req, res, next) => {
  if (typeof req.body?.completada !== 'boolean') {
    return res.status(422).json({ errores: { completada: 'Indique verdadero o falso.' } });
  }
  if (!validId(req.params.id)) return res.status(404).json({ error: 'Actividad no encontrada.' });
  try {
    const { rows: [activity] } = await db.query(
      `WITH a AS (
         UPDATE actividades SET completada = $1, updated_at = NOW() WHERE id = $2
         AND cliente_id IN (SELECT id FROM clientes WHERE vendedor_id = $3 FOR UPDATE) RETURNING *
       )
       SELECT ${columns} FROM a JOIN clientes c ON c.id = a.cliente_id`,
      [req.body.completada, req.params.id, req.user.id],
    );
    if (!activity) return res.status(404).json({ error: 'Actividad no encontrada.' });
    res.json(activity);
  } catch (error) { next(error); }
});

module.exports = { router, createActivity };
