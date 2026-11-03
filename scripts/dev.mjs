import { spawn, spawnSync } from 'node:child_process'
import { existsSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const preferredPhp = 'C:\\php-8.5.11\\php.exe'

function phpBinary() {
  if (existsSync(preferredPhp)) return preferredPhp
  const probe = spawnSync('php', ['-v'], { encoding: 'utf8' })
  if (probe.status === 0) return 'php'
  console.error(`Nie znaleziono PHP. Zainstaluj je albo ustaw ścieżkę (oczekiwano ${preferredPhp}).`)
  process.exit(1)
}

const php = phpBinary()
const children = []
let stopping = false

function start(command, args, options = {}) {
  const child = spawn(command, args, { cwd: root, stdio: 'inherit', ...options })
  children.push(child)
  return child
}

function stop(code = 0) {
  if (stopping) return
  stopping = true
  for (const child of children) {
    if (!child.pid) continue
    if (process.platform === 'win32') {
      spawn('taskkill', ['/pid', String(child.pid), '/t', '/f'], { stdio: 'ignore' })
    } else {
      child.kill()
    }
  }
  setTimeout(() => process.exit(code), 300)
}

const api = start(php, ['-c', 'api/php.ini', 'api/dev.php'])
const web = start(process.platform === 'win32' ? 'npm.cmd' : 'npm', ['run', 'dev', '--prefix', 'frontend'], { shell: true })

process.on('SIGINT', () => stop(0))
process.on('SIGTERM', () => stop(0))
api.on('exit', (code) => stop(code ?? 0))
web.on('exit', (code) => stop(code ?? 0))
