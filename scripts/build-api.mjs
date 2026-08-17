import { cp, mkdir, rm, writeFile } from 'node:fs/promises'
import { existsSync } from 'node:fs'
import { spawnSync } from 'node:child_process'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const projectRootPath = dirname(dirname(fileURLToPath(import.meta.url)))
const apiSourcePath = join(projectRootPath, 'api')
const apiOutputPath = join(projectRootPath, 'dist', 'api')

const DEPLOYABLE_ENTRY_LIST = [
  'src',
  'public',
  'config',
  'bin',
  'database/migration',
  'database/seed',
  'composer.json',
]

async function copyDeployableEntry() {
  await rm(apiOutputPath, { recursive: true, force: true })
  await mkdir(apiOutputPath, { recursive: true })

  for (const entryPath of DEPLOYABLE_ENTRY_LIST) {
    const sourcePath = join(apiSourcePath, entryPath)

    if (!existsSync(sourcePath)) {
      throw new Error(`배포 대상이 존재하지 않습니다: ${sourcePath}`)
    }

    await cp(sourcePath, join(apiOutputPath, entryPath), { recursive: true })
  }

  await mkdir(join(apiOutputPath, 'database', 'storage'), { recursive: true })
  await writeFile(join(apiOutputPath, 'database', 'storage', '.gitkeep'), '')
}

function installProductionAutoloader() {
  const result = spawnSync(
    'composer',
    ['install', '--no-dev', '--optimize-autoloader', '--no-interaction'],
    { cwd: apiOutputPath, stdio: 'inherit', shell: true },
  )

  if (result.status !== 0) {
    throw new Error('composer install 이 실패했습니다. composer 가 PATH 에 있는지 확인하십시오.')
  }
}

await copyDeployableEntry()
installProductionAutoloader()

console.log(`PHP 백엔드를 배치했습니다: ${apiOutputPath}`)
