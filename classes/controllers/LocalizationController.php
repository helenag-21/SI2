<?php
require_once __DIR__ . '/../services/LocalizationService.php';

class LocalizationController {
    private LocalizationService $localizationService;

    public function __construct(string $langDir) {
        $lang = $_SESSION['lang'] ?? 'sk';
        $this->localizationService = new LocalizationService($langDir, $lang);
    }

    public function setLanguage(string $code): void {
        $_SESSION['lang'] = $code;
        $this->localizationService->setLanguage($code);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    public function translate(string $key): string {
        return $this->localizationService->translate($key);
    }

    public function getAvailableLanguages(): array {
        return $this->localizationService->getAvailableLanguages();
    }
}
