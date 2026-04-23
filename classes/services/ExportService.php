<?php
require_once __DIR__ . '/../repositories/IEntryRepository.php';
require_once __DIR__ . '/../repositories/IDiaryRepository.php';

class ExportService {
    private IEntryRepository $entryRepo;
    private IDiaryRepository $diaryRepo;

    public function __construct(IEntryRepository $entryRepo, IDiaryRepository $diaryRepo) {
        $this->entryRepo = $entryRepo;
        $this->diaryRepo = $diaryRepo;
    }

    public function exportDiary(int $diaryId, string $format): string {
        $entries = $this->entryRepo->findByDiary($diaryId);
        if (empty($entries)) throw new RuntimeException('V denníku sa nenachádzajú žiadne záznamy.');

        switch ($format) {
            case 'txt':  return $this->toTxt($entries);
            case 'html': return $this->toHtml($entries);
            case 'json':
            default:     return json_encode(array_map(fn($e) => [
                'id'      => $e->id, 'title' => $e->title, 'content' => $e->content,
                'created' => $e->createdAt, 'updated' => $e->updatedAt,
            ], $entries), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }

    private function toTxt(array $entries): string {
        $out = '';
        foreach ($entries as $e) {
            $out .= "=== " . ($e->title ?: 'Bez názvu') . " ===
";
            $out .= "Dátum: " . date('j. n. Y H:i', strtotime($e->updatedAt)) . "

";
            $out .= $e->content . "

" . str_repeat('-', 60) . "

";
        }
        return $out;
    }

    private function toHtml(array $entries): string {
        $html = "<!DOCTYPE html><html lang='sk'><head><meta charset='UTF-8'><title>Export</title></head><body>";
        foreach ($entries as $e) {
            $html .= "<h2>" . htmlspecialchars($e->title ?: 'Bez názvu') . "</h2>";
            $html .= "<p><em>" . date('j. n. Y H:i', strtotime($e->updatedAt)) . "</em></p>";
            $html .= "<div>" . nl2br(htmlspecialchars($e->content)) . "</div><hr>";
        }
        $html .= "</body></html>";
        return $html;
    }

    public function validateImportFile(string $content): bool {
        $data = json_decode($content, true);
        return is_array($data) && !empty($data);
    }
}
