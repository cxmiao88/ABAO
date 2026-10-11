<?php

namespace App\Services\Panel;

use Illuminate\Support\Facades\Log;

/**
 * ABao: 宿主 MySQL（mdserver mysql-community，端口 33206）数据库管理服务。
 *
 * 背景：新版 mdserver dev 分支已移除 /database/* API，面板无法列库/建库/删库。
 * 本服务让 panel-api（Coolify 容器内 Laravel）直连宿主 MySQL，恢复面板内数据库管理：
 *   - list(): information_schema 列出业务库（排除系统库）+ 大小/表数
 *   - create(): CREATE DATABASE + 可选建用户并授权（仅限本库）
 *   - drop(): DROP DATABASE + 级联删除专属用户
 *   - changePassword(): 改库专属用户密码
 *
 * 凭据来自 config/panel-api.php 的 panel-api.mysql_*（由 install.sh 生成时从
 * phpMyAdmin config.inc.php 提取，或 .env 的 PANEL_MYSQL_* 覆盖）。
 */
class PanelHostDatabaseService
{
    /** 允许的字符集白名单（防注入，非法回退 utf8mb4） */
    private const ALLOWED_CHARSETS = ['utf8mb4', 'utf8mb3', 'utf8', 'gbk', 'latin1', 'ascii', 'binary'];

    /** 备份根目录（容器 storage/app/backups/database ↔ 宿主 /data/coolify/backups/database，持久卷） */
    private function backupRoot(): string
    {
        return storage_path('app/backups/database');
    }

    /** 面板侧记录的库密码文件（容器 storage/app/panel ↔ 宿主 /data/coolify/panel，持久卷；仅本机明文，不上 GitHub） */
    private function pwdFile(): string
    {
        return storage_path('app/panel/host-db-passwords.json');
    }

    /** phpMyAdmin signon 一次性令牌文件（容器 storage/app/panel ↔ 宿主 /data/coolify/panel，持久卷） */
    private function signonFile(): string
    {
        return storage_path('app/panel/pma-signon.json');
    }

    /** 某库的专属管理账号（mysql.db 库级授权反查；无记录返回 null） */
    public function dbUser(string $db): ?string
    {
        $db = $this->sanitizeIdent($db, '数据库名');
        try {
            $pdo = $this->pdo();
            $st = $pdo->prepare(
                "SELECT User FROM mysql.db WHERE Db = ? AND User NOT IN ('root','mysql.sys','mysql.session','mysql.infoschema') ORDER BY User LIMIT 1"
            );
            $st->execute([$db]);
            $u = $st->fetchColumn();

            return $u === false ? null : (string) $u;
        } catch (\Throwable $e) {
            Log::warning("panel-api host db user lookup failed for {$db}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * 生成 phpMyAdmin signon 一次性令牌（仅限该库专属账号，5 分钟有效）。
     * 失败时抛异常（无专属账号 / 密码未记录），由 controller 转 JSON 错误。
     */
    public function pmaToken(string $db): string
    {
        $db = $this->sanitizeIdent($db, '数据库名');
        // 1) 专属账号（仅该库权限，MySQL 授权天然隔离）
        $user = $this->dbUser($db);
        if ($user === null) {
            throw new \RuntimeException('该数据库没有专属管理账号，无法隔离管理（请用面板为该库创建专属账号）');
        }
        // 2) 密码（面板记录；历史遗留库无记录需先改密）
        $passwords = $this->loadPasswords();
        $pwd = $passwords[$db] ?? '';
        if ($pwd === '') {
            throw new \RuntimeException('该数据库密码未记录，请先在面板修改密码后再管理');
        }
        // 3) 生成一次性令牌
        $token = bin2hex(random_bytes(24));
        $tokens = $this->loadSignonTokens();
        // 清理过期
        foreach ($tokens as $k => $v) {
            if ((int) ($v['exp'] ?? 0) < time()) {
                unset($tokens[$k]);
            }
        }
        $tokens[$token] = [
            'db' => $db,
            'user' => $user,
            'pwd' => $pwd,
            'exp' => time() + 300,
        ];
        $this->saveSignonTokens($tokens);

        return $token;
    }

    /** 校验并消费 signon 令牌（一次性），返回 [db, user, pwd] */
    public function pmaResolve(string $token): array
    {
        $token = preg_replace('/[^a-f0-9]/', '', $token);
        if (strlen($token) !== 48) {
            throw new \RuntimeException('非法令牌');
        }
        $tokens = $this->loadSignonTokens();
        if (! isset($tokens[$token])) {
            throw new \RuntimeException('令牌无效或已使用');
        }
        $rec = $tokens[$token];
        unset($tokens[$token]);
        $this->saveSignonTokens($tokens);
        if ((int) ($rec['exp'] ?? 0) < time()) {
            throw new \RuntimeException('令牌已过期');
        }

        return [
            'db' => (string) ($rec['db'] ?? ''),
            'user' => (string) ($rec['user'] ?? ''),
            'pwd' => (string) ($rec['pwd'] ?? ''),
        ];
    }

    /** 读取 signon 令牌映射 */
    private function loadSignonTokens(): array
    {
        $f = $this->signonFile();
        if (! is_file($f)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($f), true);

        return is_array($data) ? $data : [];
    }

    /** 写入 signon 令牌映射（原子写） */
    private function saveSignonTokens(array $tokens): void
    {
        $f = $this->signonFile();
        $dir = dirname($f);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $tmp = $f.'.tmp';
        file_put_contents($tmp, json_encode($tokens, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        @chmod($tmp, 0600);
        rename($tmp, $f);
    }

    /** 读取面板记录的库→密码映射 */
    private function loadPasswords(): array
    {
        $f = $this->pwdFile();
        if (! is_file($f)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($f), true);
        if (! is_array($data)) {
            return [];
        }

        return $data;
    }

    /** 写入面板记录的库→密码映射（原子写） */
    private function savePasswords(array $map): void
    {
        $f = $this->pwdFile();
        $dir = dirname($f);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $tmp = $f.'.tmp';
        file_put_contents($tmp, json_encode($map, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        @chmod($tmp, 0600);
        rename($tmp, $f);
    }

    private function pdo(): \PDO
    {
        $host = config('panel-api.mysql_host', 'host.docker.internal');
        $port = (int) config('panel-api.mysql_port', 33206);
        $user = config('panel-api.mysql_user', 'root');
        $pass = (string) config('panel-api.mysql_password', '');
        if ($pass === '') {
            throw new \RuntimeException('宿主 MySQL 密码未配置（panel-api.mysql_password 为空）');
        }
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";

        return new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    /** 业务库列表（排除系统库；含字符集/大小/表数） */
    public function list(): array
    {
        $pdo = $this->pdo();
        $rows = $pdo->query(
            "SELECT SCHEMA_NAME AS name, DEFAULT_CHARACTER_SET_NAME AS charset, DEFAULT_COLLATION_NAME AS collation
             FROM information_schema.SCHEMATA
             WHERE SCHEMA_NAME NOT IN ('information_schema','performance_schema','mysql','sys')
             ORDER BY SCHEMA_NAME"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $name = $r['name'];
            $size = 0;
            $tables = 0;
            try {
                $size = (int) $pdo->query(
                    "SELECT COALESCE(SUM(data_length + index_length), 0)
                     FROM information_schema.TABLES WHERE TABLE_SCHEMA = ".$pdo->quote($name)
                )->fetchColumn();
                $tables = (int) $pdo->query(
                    "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ".$pdo->quote($name)
                )->fetchColumn();
            } catch (\Throwable $e) {
                Log::warning("panel-api host db stat failed for {$name}: {$e->getMessage()}");
            }
            $out[] = [
                'name' => $name,
                'charset' => $r['charset'],
                'collation' => $r['collation'],
                'size' => $size,
                'tables' => $tables,
                // 该库专属管理账号（mysql.db 反查；无则空 → 前端提示无法隔离管理）
                'user' => $this->dbUser($name) ?? '',
                // 面板记录的明文密码（仅通过面板创建/改密过的库有值；历史遗留库无记录显示未记录）
                'pwd' => $this->loadPasswords()[$name] ?? '',
            ];
        }

        return $out;
    }

    /** 创建数据库（+可选专属用户并授权本库） */
    public function create(string $db, ?string $user = null, ?string $pwd = null, string $charset = 'utf8mb4'): void
    {
        $db = $this->sanitizeIdent($db, '数据库名');
        $charset = $this->sanitizeCharset($charset);
        $pdo = $this->pdo();

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` DEFAULT CHARACTER SET {$charset}");
        if ($user !== null && $user !== '' && $pwd !== null && $pwd !== '') {
            $user = $this->sanitizeIdent($user, '用户名');
            $pdo->exec("CREATE USER IF NOT EXISTS '{$user}'@'%' IDENTIFIED BY ".$pdo->quote($pwd));
            $pdo->exec("GRANT ALL PRIVILEGES ON `{$db}`.* TO '{$user}'@'%'");
            $pdo->exec('FLUSH PRIVILEGES');
            // B03-48：面板记录明文密码（仅本机持久卷，供密码列显示/复制）
            $map = $this->loadPasswords();
            $map[$db] = $pwd;
            $this->savePasswords($map);
        }
    }

    /** 删除数据库（+级联删除同名专属用户） */
    public function drop(string $db): void
    {
        $db = $this->sanitizeIdent($db, '数据库名');
        $pdo = $this->pdo();
        $pdo->exec("DROP DATABASE IF EXISTS `{$db}`");
        // 同名用户一并删除（宝塔行为：删库删用户）
        try {
            $pdo->exec("DROP USER IF EXISTS '{$db}'@'%'");
            $pdo->exec('FLUSH PRIVILEGES');
        } catch (\Throwable $e) {
            Log::warning("panel-api host db drop user {$db} failed: {$e->getMessage()}");
        }
        // B03-48：删库后清除面板密码记录
        $map = $this->loadPasswords();
        if (isset($map[$db])) {
            unset($map[$db]);
            $this->savePasswords($map);
        }
    }

    /** 改库专属用户密码 */
    public function changePassword(string $db, string $user, string $pwd): void
    {
        $db = $this->sanitizeIdent($db, '数据库名');
        $user = $this->sanitizeIdent($user, '用户名');
        if ($pwd === '' || strlen($pwd) < 6) {
            throw new \RuntimeException('密码至少 6 位');
        }
        $pdo = $this->pdo();
        $pdo->exec("ALTER USER '{$user}'@'%' IDENTIFIED BY ".$pdo->quote($pwd));
        $pdo->exec('FLUSH PRIVILEGES');
        // B03-48：改密后同步面板记录
        $map = $this->loadPasswords();
        $map[$db] = $pwd;
        $this->savePasswords($map);
    }

    /** MySQL 用户列表（排除系统内置账户） */
    public function users(): array
    {
        $pdo = $this->pdo();
        $rows = $pdo->query(
            "SELECT User, Host FROM mysql.user
             WHERE User NOT IN ('root','mysql.sys','mysql.session','mysql.infoschema')
               AND User <> ''
             ORDER BY User"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn ($r) => ['user' => $r['User'], 'host' => $r['Host']], $rows);
    }

    /** 备份数据库（PHP 纯代码导出，不依赖宿主机 mysqldump）→ 返回备份文件信息 */
    public function backup(string $db): array
    {
        $db = $this->sanitizeIdent($db, '数据库名');
        $pdo = $this->pdo();
        $dir = $this->backupRoot().'/'.$db;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ts = date('Ymd_His');
        $file = "{$dir}/{$db}_{$ts}.sql";
        $out = "-- ABao backup of `{$db}` @ {$ts}\nSET FOREIGN_KEY_CHECKS=0;\n";
        $out .= "CREATE DATABASE IF NOT EXISTS `{$db}` DEFAULT CHARACTER SET utf8mb4;\nUSE `{$db}`;\n";

        $tables = $pdo->query("SHOW TABLES FROM `{$db}`")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $t) {
            $t = $this->sanitizeTable($t);
            $out .= "\nDROP TABLE IF EXISTS `{$t}`;\n";
            $create = $pdo->query("SHOW CREATE TABLE `{$db}`.`{$t}`")->fetch(\PDO::FETCH_ASSOC);
            $out .= $create['Create Table'].";\n";
        }
        // 数据（每 500 行合并一条多值 INSERT，避免逐行开销）
        foreach ($tables as $t) {
            $t = $this->sanitizeTable($t);
            $rows = $pdo->query("SELECT * FROM `{$db}`.`{$t}`")->fetchAll(\PDO::FETCH_NUM);
            $batch = [];
            $n = 0;
            foreach ($rows as $row) {
                $vals = [];
                foreach ($row as $v) {
                    $vals[] = $v === null ? 'NULL' : $pdo->quote($v);
                }
                $batch[] = '('.implode(',', $vals).')';
                $n++;
                if ($n >= 500) {
                    $out .= "INSERT INTO `{$t}` VALUES ".implode(',', $batch).";\n";
                    $batch = [];
                    $n = 0;
                }
            }
            if (! empty($batch)) {
                $out .= "INSERT INTO `{$t}` VALUES ".implode(',', $batch).";\n";
            }
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($file, $out);

        return ['file' => basename($file), 'size' => filesize($file)];
    }

    /** 某库的备份文件列表（文件名/大小/时间，按时间倒序） */
    public function backupList(string $db): array
    {
        $db = $this->sanitizeIdent($db, '数据库名');
        $dir = $this->backupRoot().'/'.$db;
        if (! is_dir($dir)) {
            return [];
        }
        $out = [];
        foreach (glob($dir.'/*.sql') ?: [] as $f) {
            $out[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'time' => date('Y-m-d H:i:s', filemtime($f)),
            ];
        }
        usort($out, fn ($a, $b) => strcmp($b['name'], $a['name']));

        return $out;
    }

    /** 恢复备份（先清空目标库表，再执行备份 SQL） */
    public function restore(string $db, string $file): void
    {
        $db = $this->sanitizeIdent($db, '数据库名');
        $path = $this->resolveBackupFile($db, $file);
        $pdo = $this->pdo();
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}`");
        $sql = (string) file_get_contents($path);
        // 备份内 USE 原库，恢复目标即该库（同名）；逐条执行
        $stmts = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($stmts as $stmt) {
            if ($stmt !== '' && ! str_starts_with($stmt, '--')) {
                $pdo->exec($stmt);
            }
        }
    }

    /** 删除备份文件 */
    public function deleteBackup(string $db, string $file): void
    {
        $path = $this->resolveBackupFile($db, $file);
        if (! unlink($path)) {
            throw new \RuntimeException('备份文件删除失败');
        }
    }

    /** 校验并返回备份文件绝对路径（防目录穿越） */
    public function resolveBackupFile(string $db, string $file): string
    {
        $db = $this->sanitizeIdent($db, '数据库名');
        if (! preg_match('/^[a-zA-Z0-9_]+\.sql$/', $file)) {
            throw new \RuntimeException('非法备份文件名');
        }
        $path = realpath($this->backupRoot().'/'.$db.'/'.$file);
        $root = realpath($this->backupRoot());
        if ($path === false || $root === false || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('备份文件不存在');
        }

        return $path;
    }

    /** 表名安全化（仅去掉反引号，保留原字符避免误改真实表名） */
    private function sanitizeTable(string $t): string
    {
        return str_replace('`', '', $t);
    }


    /** 标识符白名单（字母数字下划线；防止 SQL 注入与乱码） */
    private function sanitizeIdent(string $v, string $label): string
    {
        $v = preg_replace('/[^a-zA-Z0-9_]/', '', $v);
        if ($v === '' || strlen($v) > 64) {
            throw new \RuntimeException("非法{$label}");
        }

        return $v;
    }

    private function sanitizeCharset(string $cs): string
    {
        $cs = strtolower(trim($cs));
        if (! in_array($cs, self::ALLOWED_CHARSETS, true)) {
            return 'utf8mb4';
        }

        return $cs;
    }
}
