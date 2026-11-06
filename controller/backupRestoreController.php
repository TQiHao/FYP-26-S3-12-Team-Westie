<?php

require_once "../database/database.php";
require_once "../entity/backupRecords.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['system_admin']);

class BackupRestoreController
{
    public const PER_PAGE = 10;

    private $dbConfig;
    private $backupDir;
    private $toolFolders = null;

    public function __construct()
    {
        $database = new Database();
        $this->dbConfig = $database->getConfig();
        $this->backupDir = dirname(__DIR__) . '/backups';
    }

    public function getBackups($page)
    {
        $result = BackupRecords::getPage($page, self::PER_PAGE);

        foreach ($result['rows'] as &$row) {
            $row['fileExists'] = $this->resolveBackupFile($row['fileName']) !== null;
        }
        unset($row);

        return $result;
    }

    // Create a backup
    public function createBackup($adminId, $type = 'manual')
    {
        set_time_limit(300);

        if (!$this->prepareBackupFolder()) {
            return $this->failedBackup($adminId, $type, null, "The backups folder could not be created.");
        }

        $prefix   = $type === 'pre_restore' ? 'prerestore_' : 'backup_';
        $fileName = $prefix . date('Ymd_His') . '.sql';
        $fullPath = $this->backupDir . '/' . $fileName;

        if (!function_exists('proc_open')) {
            return $this->failedBackup($adminId, $type, $fileName,
                "This server does not allow programs to be run, so database backups cannot be made here.");
        }

        $binary = $this->findBinary('mysqldump');
        if ($binary === null) {
            return $this->failedBackup($adminId, $type, $fileName,
                "The MySQL backup tool (mysqldump) could not be found on this server.");
        }

        $command = [
            $binary,
            '--protocol=TCP',
            '--host=' . $this->dbConfig['host'],
            '--port=' . $this->dbConfig['port'],
            '--user=' . $this->dbConfig['username'],
            '--single-transaction',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--result-file=' . $fullPath,
            $this->dbConfig['database'],
        ];

        [$exitCode, $errorText] = $this->runProcess($command, null);

        if ($exitCode !== 0 || !is_file($fullPath) || filesize($fullPath) === 0) {
            if (is_file($fullPath)) {
                unlink($fullPath);
            }
            return $this->failedBackup($adminId, $type, $fileName, $errorText ?: "mysqldump did not produce a file.");
        }

        $id = BackupRecords::insert($fileName, $type, 'completed', $adminId);

        SystemLog::record(
            'backup',
            'info',
            $type === 'pre_restore' ? 'Safety Backup Created' : 'Backup Created',
            "Backup file $fileName was created."
        );

        return ['ok' => true, 'message' => "Backup created successfully.", 'id' => $id];
    }

    // Save a 'failed' record + write an error log, then report back
    private function failedBackup($adminId, $type, $fileName, $reason)
    {
        if ($fileName !== null) {
            BackupRecords::insert($fileName, $type, 'failed', $adminId);
        }

        SystemLog::record('backup', 'error', 'Backup', "System backup failed: " . substr($reason, 0, 400));

        return ['ok' => false, 'message' => "Backup failed. Please check the system logs.", 'id' => null];
    }


    // Restore a backup
    // Replace ALL current data with the data in one backup file.
    public function restoreBackup($backupId, $adminId)
    {
        set_time_limit(600);

        $record = BackupRecords::getById($backupId);

        if (!$record || $record['status'] !== 'completed') {
            return ['ok' => false, 'message' => "This backup cannot be restored."];
        }

        $file = $this->resolveBackupFile($record['fileName']);
        if ($file === null) {
            return ['ok' => false, 'message' => "The backup file could not be found on the server."];
        }

        if (!function_exists('proc_open')) {
            return ['ok' => false, 'message' => "Restore is not available on this server because it does not allow programs to be run."];
        }

        $binary = $this->findBinary('mysql');
        if ($binary === null) {
            return ['ok' => false, 'message' => "The MySQL restore tool (mysql) could not be found on this server."];
        }

        $safety = $this->createBackup($adminId, 'pre_restore');
        if (!$safety['ok']) {
            return ['ok' => false, 'message' => "Restore cancelled: the safety backup could not be created."];
        }

        // The restore overwrites every table, so keep the backup list and the log list to put back afterwards
        $snapshot = BackupRecords::snapshot();

        $command = [
            $binary,
            '--protocol=TCP',
            '--host=' . $this->dbConfig['host'],
            '--port=' . $this->dbConfig['port'],
            '--user=' . $this->dbConfig['username'],
            '--default-character-set=utf8mb4',
            $this->dbConfig['database'],
        ];

        [$exitCode, $errorText] = $this->runProcess($command, $file);

        if ($exitCode !== 0) {
            BackupRecords::rebuild($snapshot);

            SystemLog::record('backup', 'error', 'Restore',
                "Restore of {$record['fileName']} failed: " . substr($errorText, 0, 400));

            return ['ok' => false, 'message' => "Restore failed. Please check the system logs."];
        }

        BackupRecords::rebuild($snapshot);
        BackupRecords::markRestored($backupId, $adminId);

        SystemLog::record('backup', 'warning', 'Restore',
            "System data was restored from {$record['fileName']}. A safety backup was saved first.");

        return ['ok' => true, 'message' => "Backup restored successfully."];
    }

    // Make backups/ (once) and lock it so nobody can download .sql files through the browser
    private function prepareBackupFolder()
    {
        if (!is_dir($this->backupDir) && !mkdir($this->backupDir, 0755, true)) {
            return false;
        }

        $htaccess = $this->backupDir . '/.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }
        if (!is_file($this->backupDir . '/index.html')) {
            file_put_contents($this->backupDir . '/index.html', '');
        }

        return true;
    }

    private function resolveBackupFile($fileName)
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+\.sql$/', (string) $fileName)) {
            return null;
        }

        $path = $this->backupDir . '/' . $fileName;
        return is_file($path) ? $path : null;
    }

    private function findBinary($name)
    {
        $exe = $name . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');

        foreach ($this->toolFolders() as $folder) {
            if (@is_file($folder . '/' . $exe)) {
                return $folder . '/' . $exe;
            }
        }

        $probe = $this->runProcess([$exe, '--version'], null);
        return $probe[0] === 0 ? $exe : null;
    }

    private function toolFolders()
    {
        if ($this->toolFolders !== null) {
            return $this->toolFolders;
        }

        $folders = [];

        $override = getenv('UNIBEE_MYSQL_BIN');
        if ($override) {
            $folders[] = $override;
        }

        $baseDir = BackupRecords::getServerBaseDir();
        if ($baseDir) {
            $folders[] = rtrim(str_replace('\\', '/', $baseDir), '/') . '/bin';
        }

        array_push(
            $folders,
            'C:/xampp/mysql/bin',
            '/opt/lampp/bin',
            '/Applications/XAMPP/xamppfiles/bin',
            '/usr/local/mysql/bin',
            '/usr/bin'
        );

        $this->toolFolders = array_values(array_unique(array_map(function ($folder) {
            return rtrim(str_replace('\\', '/', $folder), '/');
        }, $folders)));

        return $this->toolFolders;
    }

    private function runProcess($command, $stdinFile)
    {
        if (!function_exists('proc_open')) {
            return [-1, "This server does not allow programs to be run (proc_open is disabled)."];
        }
        
        $descriptors = [
            0 => $stdinFile !== null ? ['file', $stdinFile, 'r'] : ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = getenv();
        if ($this->dbConfig['password'] !== '') {
            $env['MYSQL_PWD'] = $this->dbConfig['password'];
        }

        $process = @proc_open($command, $descriptors, $pipes, null, $env);
        if (!is_resource($process)) {
            return [-1, "The program could not be started."];
        }

        if ($stdinFile === null) {
            fclose($pipes[0]);
        }

        stream_get_contents($pipes[1]);
        $errorText = trim((string) stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $errorText];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_action'])) {

    $controller = new BackupRestoreController();
    $adminId    = $_SESSION['user_id'];

    // CSRF check: the page puts a secret token in the form, and it must match the one in the session
    $sentToken = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['backup_csrf']) || !hash_equals($_SESSION['backup_csrf'], $sentToken)) {
        $_SESSION['backup_error'] = "Your session expired. Please try again.";
        header("Location: ../boundary/backupRestorePage.php");
        exit();
    }

    if ($_POST['backup_action'] === 'create') {
        $result = $controller->createBackup($adminId, 'manual');
    } elseif ($_POST['backup_action'] === 'restore') {
        $result = $controller->restoreBackup((int) ($_POST['backup_id'] ?? 0), $adminId);
    } else {
        $result = ['ok' => false, 'message' => "Unknown action."];
    }

    $_SESSION[$result['ok'] ? 'backup_success' : 'backup_error'] = $result['message'];

    header("Location: ../boundary/backupRestorePage.php");
    exit();
}