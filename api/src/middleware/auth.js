const jwt = require('jsonwebtoken');
const db = require('../db');

function signToken(user) {
  return jwt.sign(
    { sub: user.id, rol: user.rol, email: user.email },
    process.env.JWT_SECRET || 'development-secret',
    { expiresIn: '8h' },
  );
}

async function requireAuth(req, res, next) {
  try {
    const header = req.get('authorization') || '';
    const token = header.startsWith('Bearer ') ? header.slice(7) : null;
    if (!token) return res.status(401).json({ mensaje: 'Token requerido.' });

    const payload = jwt.verify(token, process.env.JWT_SECRET || 'development-secret');
    const { rows } = await db.query(
      'SELECT id, name, email, rol, supervisor_id, activo FROM users WHERE id = $1 LIMIT 1',
      [payload.sub],
    );
    if (!rows[0] || !rows[0].activo) return res.status(401).json({ mensaje: 'Usuario inactivo o inexistente.' });
    req.user = rows[0];
    next();
  } catch (error) {
    if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
      return res.status(401).json({ mensaje: 'Token inválido o expirado.' });
    }
    next(error);
  }
}

function requireVendor(req, res, next) {
  if (req.user?.rol !== 'vendedor') return res.status(403).json({ mensaje: 'Solo los vendedores pueden usar esta operación.' });
  next();
}

module.exports = { requireAuth, requireVendor, signToken };
