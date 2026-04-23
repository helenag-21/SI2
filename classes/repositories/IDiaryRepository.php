<?php
interface IDiaryRepository {
    public function findById(int $id): ?Diary;
    public function findByUser(int $userId): array;
    public function save(Diary $diary): Diary;
    public function delete(int $id): bool;
}
