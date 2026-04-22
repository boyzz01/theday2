// scripts/make-template.js
import { readFileSync, writeFileSync, existsSync } from 'fs'
import { join, dirname } from 'path'
import { fileURLToPath } from 'url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = join(__dirname, '..')

const slug = process.argv[2]

if (!slug) {
    console.error('❌  Usage: npm run make:template <slug>')
    console.error('    Contoh: npm run make:template jasmine')
    process.exit(1)
}

if (!/^[a-z][a-z0-9-]*$/.test(slug)) {
    console.error('❌  Slug harus huruf kecil, angka, atau dash.')
    console.error('    Contoh valid: jasmine, royal-garden, bali-sunset')
    process.exit(1)
}

// 'jasmine' → 'JasmineTemplate', 'royal-garden' → 'RoyalGardenTemplate'
const componentName = slug.split('-')
    .map(s => s[0].toUpperCase() + s.slice(1))
    .join('') + 'Template'

const filename      = `${componentName}.vue`
const templatesDir  = join(root, 'resources/js/Components/invitation/templates')
const targetPath    = join(templatesDir, filename)
const boilerplate   = join(templatesDir, '_template-boilerplate.vue')
const registryPath  = join(templatesDir, 'registry.js')

// Guard: target already exists
if (existsSync(targetPath)) {
    console.error(`❌  ${filename} sudah ada.`)
    process.exit(1)
}

// Guard: boilerplate must exist
if (!existsSync(boilerplate)) {
    console.error(`❌  _template-boilerplate.vue tidak ditemukan di ${templatesDir}`)
    process.exit(1)
}

// 1. Copy boilerplate → new template file
writeFileSync(targetPath, readFileSync(boilerplate, 'utf8'))
console.log(`✓  Buat ${filename}`)

// 2. Update registry.js
let registry = readFileSync(registryPath, 'utf8')

// Insert import after last import line
const lastImportIdx = registry.lastIndexOf('\nimport ')
const insertAt = registry.indexOf('\n', lastImportIdx + 1)
registry =
    registry.slice(0, insertAt) +
    `\nimport ${componentName} from './${componentName}.vue'` +
    registry.slice(insertAt)

// Insert map entry before closing }
const mapCloseIdx = registry.lastIndexOf('}')
registry =
    registry.slice(0, mapCloseIdx) +
    `    '${slug}': ${componentName},\n` +
    registry.slice(mapCloseIdx)

writeFileSync(registryPath, registry)
console.log(`✓  Register '${slug}' di registry.js`)

// 3. Print checklist
console.log(`
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ✅  Template siap: ${componentName}
  📄  ${filename}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Checklist konversi HTML → Vue:
  ☐  Prep HTML (hapus CDN/JS kompetitor)
  ☐  Replace hardcoded warna → primary, accent, bgColor
  ☐  Replace hardcoded font  → fontTitle, fontHeading, fontBody
  ☐  Map setiap section HTML → section key (lihat spec)
  ☐  Wrap tiap section: v-if="sectionEnabled('key')"
  ☐  Set galleryLayout + openingStyle di composable call
  ☐  Insert DB record + demo_data lengkap
  ☐  Test di http://127.0.0.1:8000/templates/${slug}/demo
  ☐  Screenshot → upload sebagai thumbnail_url

📸  Foto demo (gunakan di demo_data):
  Cover / couple : /image/demo-image/bride-groom.png
  Foto bride     : /image/demo-image/bride.png
  Foto groom     : /image/demo-image/groom.png
  Gallery (x4)   : /image/demo-image/bride-groom.png
`)
