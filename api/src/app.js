require('dotenv').config();

const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const { requireAuth } = require('./middleware/auth');
const authRoutes = require('./routes/auth');
const clientRoutes = require('./routes/clientes');
const opportunityRoutes = require('./routes/oportunidades');
const activityRoutes = require('./routes/actividades');
const reportRoutes = require('./routes/reportes');

const app = express();
app.use(helmet());
app.use(cors());
app.use(express.json());
app.use(morgan('combined'));

app.get('/health', (_req, res) => res.json({ estado: 'ok', servicio: 'crm-muebleria-api' }));
app.use('/api/auth', authRoutes);
app.use('/api/clientes', requireAuth, clientRoutes);
app.use('/api/oportunidades', requireAuth, opportunityRoutes);
app.use('/api/actividades', requireAuth, activityRoutes);
app.use('/api/reportes', requireAuth, reportRoutes);

app.use((error, _req, res, _next) => {
  console.error(error);
  res.status(500).json({ mensaje: 'Error interno del servidor.' });
});

module.exports = app;
