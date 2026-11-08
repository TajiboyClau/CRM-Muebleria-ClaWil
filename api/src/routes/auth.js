const express = require('express');
const bcrypt = require('bcryptjs');
const db = require('../db');
const { requireAuth, signToken } = require('../middleware/auth');

const router = express.Router();
const publicUser = user => ({ id: user.id, nombre: user.name, correo: user.email });

router.post('/login', async (req, res, next) => {
  try {
    if (!req.body || typeof req.body !== 'object' || Array.isArray(req.body)) {
      return res.status(400).json({ error: 'Envíe un objeto JSON.' });
    }
    const { correo, clave } = req.body;
    const errores = {};
    if (typeof correo !== 'string' || !correo.trim()) errores.correo = 'Ingrese su correo.';
    if (typeof clave !== 'string' || !clave) errores.clave = 'Ingrese su contraseña.';
    if (Object.keys(errores).length) return res.status(422).json({ errores });

    const { rows: [user] } = await db.query(
      'SELECT id, name, email, password, rol, activo FROM users WHERE email = $1 LIMIT 1',
      [correo.trim()],
    );
    if (!user || !(await bcrypt.compare(clave, user.password))) {
      return res.status(401).json({ error: 'Credenciales inválidas.' });
    }
    if (!user.activo || user.rol !== 'vendedor') {
      return res.status(403).json({ error: 'Esta cuenta no tiene acceso al portal de vendedores.' });
    }

    res.json({ token: signToken(user), usuario: publicUser(user) });
  } catch (error) { next(error); }
});

router.get('/me', requireAuth, (req, res) => res.json({ usuario: publicUser(req.user) }));

module.exports = router;
