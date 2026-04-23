<?php
require_once __DIR__ . '/../services/DiaryService.php';
require_once __DIR__ . '/../repositories/DiaryRepository.php';

class DiaryController {
    private DiaryService $diaryService;

    public function __construct(PDO $pdo) {
        $this->diaryService = new DiaryService(new DiaryRepository($pdo));
    }

    public function listDiaries(int $userId): array {
        return $this->diaryService->listDiaries($userId);
    }

    public function createDiary(int $userId, string $title): Diary {
        return $this->diaryService->createDiary($userId, $title);
    }

    public function renameDiary(int $id, int $userId, string $newTitle): Diary {
        return $this->diaryService->renameDiary($id, $userId, $newTitle);
    }

    public function deleteDiary(int $id, int $userId): bool {
        return $this->diaryService->deleteDiary($id, $userId);
    }

    public function showDiary(int $id, int $userId): Diary {
        return $this->diaryService->getDiary($id, $userId);
    }
}
