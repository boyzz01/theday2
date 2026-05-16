// scripts/lang-check.cjs
const fs = require('fs');
const path = require('path');

const en = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'lang', 'en.json'), 'utf8'));
const id = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'lang', 'id.json'), 'utf8'));

function flatten(obj, prefix = '') {
  const out = [];
  for (const [k, v] of Object.entries(obj)) {
    const key = prefix ? `${prefix}.${k}` : k;
    if (v && typeof v === 'object' && !Array.isArray(v)) {
      out.push(...flatten(v, key));
    } else {
      out.push(key);
    }
  }
  return out;
}

const enKeys = new Set(flatten(en));
const idKeys = new Set(flatten(id));

const missingInEn = [...idKeys].filter(k => !enKeys.has(k));
const missingInId = [...enKeys].filter(k => !idKeys.has(k));

if (missingInEn.length === 0 && missingInId.length === 0) {
  console.log(`OK — ${enKeys.size} keys consistent between en.json and id.json`);
  process.exit(0);
}

if (missingInEn.length) {
  console.error(`Missing in en.json (${missingInEn.length}):`);
  missingInEn.forEach(k => console.error(`  - ${k}`));
}
if (missingInId.length) {
  console.error(`Missing in id.json (${missingInId.length}):`);
  missingInId.forEach(k => console.error(`  - ${k}`));
}
process.exit(1);
