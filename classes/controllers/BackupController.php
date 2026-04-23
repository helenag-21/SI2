<?php
require_once __DIR__ . '/../services/BackupService.php';
require_once __DIR__ . '/../repositories/BackupRepository.php';
require_once __DIR__ . '/../repositories/EntryRepository.php';

class BackupController {
    private BackupService $backupService;

    public function __construct(PDO $pdo) {
        $this->backupService = new BackupService(
            new BackupRepository($pdo),
            new EntryRepository($pdo)
        );
    }

    public function createBackup(int $diaryId, string $diaryTitle): Backup {
        return $this->backupService->createBackup($diaryId, $diaryTitle);
    }

    public function listBackups(int $diaryId): array {
        return $this->backupService->listBackups($diaryId);
    }

    public function deleteBackup(int $id): bool {
        return $this->backupService->deleteBackup($id);
    }

    public function validateBackup(int $id): bool {
        return $this->backupService->validateBackup($id);
    }
}
