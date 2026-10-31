<?php

require_once "../database/database.php";
require_once "../entity/aiModelVersions.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'system_admin'
) {
    header("Location: ../boundary/loginPage.php");
    exit();
}

class UpdateAIModelController
{
    // File types a model can be uploaded as
    public const ALLOWED_EXTENSIONS = ['gguf', 'bin', 'onnx', 'safetensors'];

    public const REQUIRE_SERVER_CONFIRMATION = false;

    private $models;
    private $modelDir;
    private $configFile;
    private $configBackupDir;

    public function __construct()
    {
        $this->models          = new AIModelVersions();
        $this->modelDir        = dirname(__DIR__) . '/models';
        $this->configFile      = $this->modelDir . '/active_model.json';
        $this->configBackupDir = $this->modelDir . '/config_backups';
    }

    public function getCurrentModel()
    {
        return $this->models->getDeployed();
    }

    public function getHistory()
    {
        return $this->models->getHistory(10);
    }

    public function getStagedUpdate($id, $adminId)
    {
        $row = $this->models->getById($id);

        if (
            !$row ||
            $row['status'] !== AIModelVersions::STATUS_APPROVED ||
            (int) $row['updatedBy'] !== (int) $adminId
        ) {
            return false;
        }

        return $row;
    }

    public function getUploadLimitText()
    {
        $bytes = min(
            $this->iniToBytes(ini_get('upload_max_filesize')),
            $this->iniToBytes(ini_get('post_max_size')) ?: PHP_INT_MAX
        );

        return $this->formatBytes($bytes);
    }

    public function stageUpdate($input, $file, $adminId)
    {
        $modelName    = trim($input['modelName'] ?? '');
        $version      = trim($input['version'] ?? '');
        $releaseNotes = trim($input['releaseNotes'] ?? '');

        $problem = $this->validateInput($modelName, $version, $releaseNotes, $file);

        if ($problem !== null) {
            SystemLog::record('ai_model', 'warning', 'AI Model Validation Failed',
                "Update to '$modelName' '$version' was rejected: $problem");
            return ['ok' => false, 'message' => "The AI model update could not be validated. " . $problem];
        }

        foreach ($this->models->deleteUnusedApproved($adminId) as $oldPath) {
            $this->deleteModelFile($oldPath);
        }

        if (!is_dir($this->modelDir) && !@mkdir($this->modelDir, 0775, true)) {
            return ['ok' => false, 'message' => "The AI model update could not be validated. The models folder could not be created."];
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
        $stored   = bin2hex(random_bytes(6)) . '_' . substr($safeName, -80);
        $target   = $this->modelDir . '/' . $stored;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['ok' => false, 'message' => "The AI model update could not be validated. The file could not be saved."];
        }

        $current = $this->models->getDeployed();
        $id      = $this->models->createApproved($modelName, $version, $releaseNotes, 'models/' . $stored, $adminId);

        SystemLog::record('ai_model', 'info', 'AI Model Validated',
            "$modelName $version passed validation and is waiting for confirmation.");

        return [
            'ok'      => true,
            'id'      => $id,
            'message' => 'Validated.',
            'from'    => $current ? $current['version'] : null,
        ];
    }

    private function validateInput($modelName, $version, $releaseNotes, $file)
    {
        if ($modelName === '' || $version === '' || $releaseNotes === '') {
            return "Model name, version and release notes are all required.";
        }
        if (mb_strlen($modelName) > 255) {
            return "The model name is too long.";
        }
        if (mb_strlen($releaseNotes) > 2000) {
            return "The release notes are too long (2000 characters at most).";
        }
        if (!preg_match('/^v?\d+(\.\d+){0,3}([-+][A-Za-z0-9.]+)?$/', $version) || strlen($version) > 50) {
            return "The version must look like v3.0 or 2.1.4.";
        }

        if (!$file || !isset($file['error'])) {
            return "Please choose a model file.";
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->uploadErrorText($file['error']);
        }
        if (!is_uploaded_file($file['tmp_name']) || (int) $file['size'] <= 0) {
            return "The model file is empty or was not uploaded properly.";
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return "Only " . implode(', ', array_map(function ($e) {
                return '.' . $e;
            }, self::ALLOWED_EXTENSIONS)) . " files are accepted.";
        }

        if ($extension === 'gguf') {
            $handle = @fopen($file['tmp_name'], 'rb');
            $magic  = $handle ? fread($handle, 4) : '';
            if ($handle) {
                fclose($handle);
            }
            if ($magic !== 'GGUF') {
                return "The file is not a valid GGUF model.";
            }
        }

        if ($this->models->versionExists($modelName, $version)) {
            return "$modelName $version is already registered.";
        }

        $current = $this->models->getDeployed();
        if ($current && $current['modelName'] === $modelName && $current['version'] === $version) {
            return "$modelName $version is already the live version.";
        }

        return null;
    }

    public function confirmUpdate($id, $adminId)
    {
        $staged = $this->getStagedUpdate($id, $adminId);
        if (!$staged) {
            return ['ok' => false, 'message' => "There is no validated update waiting. Please upload the model again."];
        }

        $modelPath = dirname(__DIR__) . '/' . $staged['filePath'];
        if (!is_file($modelPath)) {
            $this->models->markFailed($id);
            return ['ok' => false, 'message' => "The uploaded model file is missing. Please upload it again."];
        }

        $current  = $this->models->getDeployed();
        $label    = $staged['modelName'] . ' ' . $staged['version'];
        $restored = $current ? "The previous version had been restored." : "No previous version was changed.";

        $previousConfig = $this->backupConfig();

        $config = [
            'modelName'   => $staged['modelName'],
            'version'     => $staged['version'],
            'filePath'    => realpath($modelPath),
            'activatedAt' => date('c'),
        ];

        if (!$this->writeConfig($config)) {
            $this->restoreConfig($previousConfig);
            $this->discardFailedInstall($staged);
            SystemLog::record('ai_model', 'error', 'AI Model Update Failed', "$label could not be installed: configuration could not be written.");
            return ['ok' => false, 'message' => "AI model update failed. " . $restored];
        }

        $server = $this->notifyAiServer($config);

        if (
            $server['state'] === 'failed' ||
            ($server['state'] === 'unconfirmed' && self::REQUIRE_SERVER_CONFIRMATION)
        ) {
            $this->restoreConfig($previousConfig);
            $this->discardFailedInstall($staged);
            SystemLog::record('ai_model', 'error', 'AI Model Update Failed', "$label could not be loaded by the AI server: " . $server['detail']);
            return ['ok' => false, 'message' => "AI model update failed. " . $restored];
        }

        if (!$this->models->replaceDeployed($current ? $current['id'] : null, $id, $adminId)) {
            $this->restoreConfig($previousConfig);
            $this->discardFailedInstall($staged);
            SystemLog::record('ai_model', 'error', 'AI Model Update Failed', "$label could not be saved to the database.");
            return ['ok' => false, 'message' => "AI model update failed. " . $restored];
        }

        $from = $current ? " (previous: " . $current['modelName'] . ' ' . $current['version'] . ")" : "";
        SystemLog::record('ai_model', 'info', 'AI Model Updated', "$label is now the live AI model$from.");

        $note = null;
        if ($server['state'] === 'unconfirmed') {
            $note = "The AI server did not confirm the reload. Restart it so it loads the new model.";
            SystemLog::record('ai_model', 'warning', 'AI Server Not Confirmed', "$label was activated but the AI server did not confirm it: " . $server['detail']);
        }

        return ['ok' => true, 'message' => "AI Model has been Updated Successfully.", 'note' => $note];
    }

    private function discardFailedInstall($staged)
    {
        $this->models->markFailed($staged['id']);
        $this->deleteModelFile($staged['filePath']);
    }

    public function cancelUpdate($id, $adminId)
    {
        $staged = $this->getStagedUpdate($id, $adminId);

        if ($staged) {
            $this->models->delete($id);
            $this->deleteModelFile($staged['filePath']);
            SystemLog::record('ai_model', 'info', 'AI Model Update Cancelled',
                $staged['modelName'] . ' ' . $staged['version'] . " was not installed.");
        }
    }

    private function backupConfig()
    {
        if (!is_file($this->configFile)) {
            return null;
        }

        $previous = file_get_contents($this->configFile);

        if (is_dir($this->configBackupDir) || @mkdir($this->configBackupDir, 0775, true)) {
            @file_put_contents($this->configBackupDir . '/active_model_' . date('Ymd_His') . '.json', $previous);
        }

        return $previous === false ? null : $previous;
    }

    private function writeConfig($config)
    {
        if (!is_dir($this->modelDir) && !@mkdir($this->modelDir, 0775, true)) {
            return false;
        }

        $temp = $this->configFile . '.tmp';
        if (@file_put_contents($temp, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            return false;
        }

        return @rename($temp, $this->configFile);
    }

    private function restoreConfig($previousConfig)
    {
        if ($previousConfig === null) {
            @unlink($this->configFile);
        } else {
            @file_put_contents($this->configFile, $previousConfig);
        }
    }

    protected function notifyAiServer($config)
    {
        if (!function_exists('curl_init')) {
            return ['state' => 'unconfirmed', 'detail' => 'PHP cURL is not enabled.'];
        }

        $url = rtrim($this->aiServerUrl(), '/') . '/reload';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($config));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $response  = curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 0) {
            return ['state' => 'unconfirmed', 'detail' => "AI server not reachable ($curlError)."];
        }
        if ($httpCode === 404 || $httpCode === 405) {
            return ['state' => 'unconfirmed', 'detail' => "AI server has no /reload route (HTTP $httpCode)."];
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            return ['state' => 'failed', 'detail' => "AI server answered HTTP $httpCode."];
        }

        $data = json_decode((string) $response, true);
        if (is_array($data) && array_key_exists('ok', $data) && $data['ok'] === false) {
            return ['state' => 'failed', 'detail' => $data['error'] ?? 'AI server reported an error.'];
        }

        return ['state' => 'confirmed', 'detail' => 'ok'];
    }

    private function aiServerUrl()
    {
        return getenv('UNIBEE_AI_URL') ?: 'http://127.0.0.1:5000';
    }

    private function deleteModelFile($relativePath)
    {
        if (!$relativePath) {
            return;
        }

        $full = realpath(dirname(__DIR__) . '/' . $relativePath);
        $root = realpath($this->modelDir);

        if ($full && $root && strpos($full, $root . DIRECTORY_SEPARATOR) === 0 && is_file($full)) {
            @unlink($full);
        }
    }

    private function uploadErrorText($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "The file is larger than this server allows (" . $this->getUploadLimitText() . ").";
            case UPLOAD_ERR_NO_FILE:
                return "Please choose a model file.";
            case UPLOAD_ERR_PARTIAL:
                return "The file was only partly uploaded. Please try again.";
            default:
                return "The file could not be uploaded.";
        }
    }

    private function iniToBytes($value)
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0' || $value === '-1') {
            return 0;
        }

        $number = (float) $value;
        switch (strtolower(substr($value, -1))) {
            case 'g':
                $number *= 1024;
            case 'm':
                $number *= 1024;
            case 'k':
                $number *= 1024;
        }

        return (int) $number;
    }

    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1) . ' GB';
        }
        return max(1, (int) round($bytes / 1048576)) . ' MB';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $backTo = "../boundary/updateAIModelPage.php";

    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['ai_model_error'] = "The file is larger than this server allows (" . (new UpdateAIModelController())->getUploadLimitText() . ").";
        header("Location: $backTo");
        exit();
    }

    if (isset($_POST['ai_action'])) {

        $controller = new UpdateAIModelController();
        $adminId    = $_SESSION['user_id'];

        $sentToken = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['ai_model_csrf']) || !hash_equals($_SESSION['ai_model_csrf'], $sentToken)) {
            $_SESSION['ai_model_error'] = "Your session expired. Please try again.";
            header("Location: $backTo");
            exit();
        }

        if ($_POST['ai_action'] === 'stage') {

            $result = $controller->stageUpdate($_POST, $_FILES['modelFile'] ?? null, $adminId);

            if ($result['ok']) {
                header("Location: $backTo?confirm=" . $result['id']);
                exit();
            }

            $_SESSION['ai_model_error'] = $result['message'];
            $_SESSION['ai_model_old']   = [
                'modelName'    => $_POST['modelName'] ?? '',
                'version'      => $_POST['version'] ?? '',
                'releaseNotes' => $_POST['releaseNotes'] ?? '',
            ];

        } elseif ($_POST['ai_action'] === 'confirm') {

            $result = $controller->confirmUpdate((int) ($_POST['model_id'] ?? 0), $adminId);

            if ($result['ok']) {
                $_SESSION['ai_model_success'] = $result['message'];
                if (!empty($result['note'])) {
                    $_SESSION['ai_model_note'] = $result['note'];
                }
            } else {
                $_SESSION['ai_model_error'] = $result['message'];
            }

        } elseif ($_POST['ai_action'] === 'cancel') {

            $controller->cancelUpdate((int) ($_POST['model_id'] ?? 0), $adminId);
        }
    }

    header("Location: $backTo");
    exit();
}