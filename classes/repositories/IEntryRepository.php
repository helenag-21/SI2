<?php
interface IEntryRepository {
    public function findById(int $id): ?Entry;
    public function findByDiary(int $diaryId): array;
    public function search(string $keyword, int $diaryId): array;
    public function save(Entry $entry): Entry;
    public function delete(int $id): bool;
}
