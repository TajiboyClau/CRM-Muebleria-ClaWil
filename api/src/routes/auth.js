const express = require('express');
const bcrypt = require('bcryptjs');
const db = require('../db');
const { requireAuth, signToken } = require('../middleware/auth');

const router = express.Router();

router.post('/login', async (req, res, next) => {
  try {
    const { email, password } = req.body || {};
    if (!email || !password) return res.status(422).json({ mensaje: 'Correo y contraseña son obligatorios.' });

    const { rows } = await db.query(
      'SELECT id, name, email, password, rol, supervisor_id, activo FROM users WHERE email = $1 LIMIT 1',
      [email],
    );
    const user = rows[0];
    if (!user || user.rol !== 'vendedor' || !user.activo || !(await bcrypt.compare(password, user.password))) {
      return res.status(401).json({ mensaje: 'Credenciales inválidas.' });
    }

    const { password: _password, ...safeUser } = user;
    res.json({ token: signToken(user), usuario: safeUser });
  } catch (error) {
    next(error);
  }
});

router.get('/me', requireAuth, (req, res) => res.json({ usuario: req.user }));

module.exports = router;
