<?php
require_once __DIR__ . '/../repositories/IBackupRepository.php';
require_once __DIR__ . '/../repositories/BackupRepository.php';
require_once __DIR__ . '/../repositories/IEntryRepository.php';
require_once __DIR__ . '/../entities/Backup.php';

class BackupService {
    private IBackupRepository $backupRepo;
    private IEntryRepository $entryRepo;

    public function __construct(IBackupRepository $backupRepo, IEntryRepository $entryRepo) {
        $this->backupRepo = $backupRepo;
        $this->entryRepo  = $entryRepo;
    }

    public function createBackup(int $diaryId, string $diaryTitle): Backup {
        $entries = $this->entryRepo->findByDiary($diaryId);
        $data = json_encode([
            'dennik' => $diaryTitle,
            'datum'  => date('Y-m-d H:i:s'),
            'zapisy' => array_map(fn($e) => [
                'nazov'           => $e->title,
                'obsah'           => $e->content,
                'datum_vytvorenia'=> $e->createdAt,
                'datum_upravy'    => $e->updatedAt,
            ], $entries),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $backup = new Backup($diaryId, 'Záloha ' . date('j. n. Y H:i'), $data);
        return $this->backupRepo->save($backup);
    }

    public function validateBackup(int $id): bool {
        return $this->backupRepo->validate($id);
    }

    public function listBackups(int $diaryId): array {
        return $this->backupRepo->findByDiary($diaryId);
    }

    public function deleteBackup(int $id): bool {
        return $this->backupRepo->delete($id);
    }
}
