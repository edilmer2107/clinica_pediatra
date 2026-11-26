// Dimensiones de una imagen PNG (cabecera IHDR)
const fs = require('fs');
module.exports = f => { const b = fs.readFileSync(f); return { width: b.readUInt32BE(16), height: b.readUInt32BE(20) }; };
