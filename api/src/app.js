require('dotenv').config();

const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const { requireAuth, requireVendor } = require('./middleware/auth');
const db = require('./db');
const authRoutes = require('./routes/auth');
const clientRoutes = require('./routes/clientes');
const opportunityRoutes = require('./routes/oportunidades');
const { router: activityRoutes, createActivity } = require('./routes/actividades');
const reportRoutes = require('./routes/reportes');

const app = express();
app.use(helmet());
app.use(cors());
app.use(express.json());
app.use(morgan('combined'));

app.get('/health', (_req, res) => res.json({ estado: 'ok', servicio: 'crm-muebleria-api' }));
app.use('/api/auth', authRoutes);
app.get('/api/productos', requireAuth, requireVendor, async (_req, res, next) => {
  try {
    const { rows } = await db.query('SELECT id, nombre, precio::float8 AS precio FROM productos WHERE activo = TRUE ORDER BY nombre');
    res.json(rows);
  } catch (error) { next(error); }
});
app.post('/api/clientes/:id/actividades', requireAuth, requireVendor, createActivity);
app.use('/api/clientes', requireAuth, clientRoutes);
app.use('/api/oportunidades', requireAuth, opportunityRoutes);
app.use('/api/actividades', requireAuth, activityRoutes);
app.use('/api/reportes', requireAuth, reportRoutes);

app.use((_req, res) => res.status(404).json({ error: 'Ruta no encontrada.' }));

app.use((error, _req, res, _next) => {
  if (error.type === 'entity.parse.failed') return res.status(400).json({ error: 'JSON inválido.' });
  console.error(error);
  res.status(500).json({ error: 'Error interno del servidor.' });
});

module.exports = app;
