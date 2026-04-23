<?php
interface IBackupRepository {
    public function findByDiary(int $diaryId): array;
    public function findById(int $id): ?Backup;
    public function save(Backup $backup): Backup;
    public function delete(int $id): bool;
    public function validate(int $id): bool;
}
