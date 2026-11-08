const jwt = require('jsonwebtoken');
const db = require('../db');
const secret = process.env.JWT_SECRET;
if (typeof secret !== 'string' || secret.length < 32) {
  throw new Error('Configure JWT_SECRET con al menos 32 caracteres.');
}

function signToken(user) {
  return jwt.sign(
    { sub: user.id, rol: user.rol, email: user.email },
    secret,
    { algorithm: 'HS256', expiresIn: process.env.JWT_EXPIRES_IN || '8h' },
  );
}

async function requireAuth(req, res, next) {
  try {
    const header = req.get('authorization') || '';
    const token = header.startsWith('Bearer ') ? header.slice(7) : null;
    if (!token) return res.status(401).json({ error: 'Token requerido.' });

    const payload = jwt.verify(token, secret, { algorithms: ['HS256'] });
    const { rows } = await db.query(
      'SELECT id, name, email, rol, supervisor_id, activo FROM users WHERE id = $1 LIMIT 1',
      [payload.sub],
    );
    if (!rows[0] || !rows[0].activo || rows[0].rol !== 'vendedor') return res.status(401).json({ error: 'Usuario inactivo o inexistente.' });
    req.user = rows[0];
    next();
  } catch (error) {
    if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
      return res.status(401).json({ error: 'Token inválido o expirado.' });
    }
    next(error);
  }
}

function requireVendor(req, res, next) {
  if (req.user?.rol !== 'vendedor') return res.status(403).json({ error: 'Solo los vendedores pueden usar esta operación.' });
  next();
}

module.exports = { requireAuth, requireVendor, signToken };
