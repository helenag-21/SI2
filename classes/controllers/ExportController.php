<?php
require_once __DIR__ . '/../services/ExportService.php';
require_once __DIR__ . '/../repositories/EntryRepository.php';
require_once __DIR__ . '/../repositories/DiaryRepository.php';

class ExportController {
    private ExportService $exportService;

    public function __construct(PDO $pdo) {
        $this->exportService = new ExportService(
            new EntryRepository($pdo),
            new DiaryRepository($pdo)
        );
    }

    public function exportDiary(int $diaryId, string $format): void {
        $content = $this->exportService->exportDiary($diaryId, $format);
        $date    = date('Y-m-d');
        $ext     = $format === 'html' ? 'html' : ($format === 'txt' ? 'txt' : 'json');
        $mime    = $format === 'html' ? 'text/html' : ($format === 'txt' ? 'text/plain' : 'application/json');

        header("Content-Type: $mime; charset=utf-8");
        header("Content-Disposition: attachment; filename="export-$date.$ext"");
        echo $content;
        exit;
    }

    public function importDiary(string $jsonContent, int $userId, PDO $pdo): array {
        if (!$this->exportService->validateImportFile($jsonContent)) {
            throw new InvalidArgumentException('Neplatný formát súboru.');
        }
        // Deleguje na import.php logiku
        return json_decode($jsonContent, true);
    }
}
