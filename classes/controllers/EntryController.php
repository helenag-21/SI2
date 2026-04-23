<?php
require_once __DIR__ . '/../services/EntryService.php';
require_once __DIR__ . '/../repositories/EntryRepository.php';

class EntryController {
    private EntryService $entryService;

    public function __construct(PDO $pdo) {
        $this->entryService = new EntryService(new EntryRepository($pdo));
    }

    public function createEntry(int $diaryId, string $title, string $content, ?int $categoryId=null): Entry {
        return $this->entryService->createEntry($diaryId, $title, $content, $categoryId);
    }

    public function updateEntry(int $id, string $title, string $content, ?int $categoryId=null): Entry {
        return $this->entryService->updateEntry($id, $title, $content, $categoryId);
    }

    public function deleteEntry(int $id): bool {
        return $this->entryService->deleteEntry($id);
    }

    public function showEntry(int $id): Entry {
        return $this->entryService->getEntry($id);
    }

    public function searchEntries(string $query, int $diaryId): array {
        return $this->entryService->searchEntries($query, $diaryId);
    }

    public function showNewEntryForm(): void {
        // Prezentačná logika — renderovanie je v PHP stránke
    }
}
