<?php
require_once __DIR__ . '/../repositories/IDiaryRepository.php';
require_once __DIR__ . '/../repositories/DiaryRepository.php';
require_once __DIR__ . '/../entities/Diary.php';

class DiaryService {
    private IDiaryRepository $diaryRepo;

    public function __construct(IDiaryRepository $diaryRepo) {
        $this->diaryRepo = $diaryRepo;
    }

    public function createDiary(int $userId, string $title): Diary {
        if (trim($title) === '') throw new InvalidArgumentException('Názov denníka nesmie byť prázdny.');
        $diary = new Diary($userId, $title);
        return $this->diaryRepo->save($diary);
    }

    public function getDiary(int $id, int $userId): Diary {
        $diary = $this->diaryRepo->findById($id);
        if (!$diary) throw new RuntimeException('Denník neexistuje.');
        if ($diary->userId !== $userId) throw new RuntimeException('Nemáš prístup k tomuto denníku.');
        return $diary;
    }

    public function renameDiary(int $id, int $userId, string $newTitle): Diary {
        $diary = $this->getDiary($id, $userId);
        if (trim($newTitle) === '') throw new InvalidArgumentException('Názov nesmie byť prázdny.');
        $diary->title = $newTitle;
        return $this->diaryRepo->save($diary);
    }

    public function deleteDiary(int $id, int $userId): bool {
        $diary = $this->getDiary($id, $userId);
        return $this->diaryRepo->delete($diary->id);
    }

    public function listDiaries(int $userId): array {
        return $this->diaryRepo->findByUser($userId);
    }
}
