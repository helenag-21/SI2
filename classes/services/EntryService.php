<?php
require_once __DIR__ . '/../repositories/IEntryRepository.php';
require_once __DIR__ . '/../repositories/EntryRepository.php';
require_once __DIR__ . '/../entities/Entry.php';

class EntryService {
    private IEntryRepository $entryRepo;

    public function __construct(IEntryRepository $entryRepo) {
        $this->entryRepo = $entryRepo;
    }

    public function createEntry(int $diaryId, string $title, string $content, ?int $categoryId=null, ?int $templateId=null): Entry {
        if (trim($title) === '' && trim($content) === '') {
            throw new InvalidArgumentException('Názov alebo obsah zápisu nesmie byť prázdny.');
        }
        $entry = new Entry($diaryId, $title, $content, $categoryId, $templateId);
        return $this->entryRepo->save($entry);
    }

    public function updateEntry(int $id, string $title, string $content, ?int $categoryId=null): Entry {
        $entry = $this->entryRepo->findById($id);
        if (!$entry) throw new RuntimeException('Zápis neexistuje.');
        $entry->title = $title;
        $entry->content = $content;
        $entry->categoryId = $categoryId;
        return $this->entryRepo->save($entry);
    }

    public function deleteEntry(int $id): bool {
        $entry = $this->entryRepo->findById($id);
        if (!$entry) throw new RuntimeException('Zápis neexistuje.');
        return $this->entryRepo->delete($id);
    }

    public function getEntry(int $id): Entry {
        $entry = $this->entryRepo->findById($id);
        if (!$entry) throw new RuntimeException('Zápis neexistuje.');
        return $entry;
    }

    public function getEntriesByDiary(int $diaryId): array {
        return $this->entryRepo->findByDiary($diaryId);
    }

    public function searchEntries(string $keyword, int $diaryId): array {
        if (trim($keyword) === '') return $this->entryRepo->findByDiary($diaryId);
        return $this->entryRepo->search($keyword, $diaryId);
    }
}
