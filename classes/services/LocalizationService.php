<?php
require_once __DIR__ . '/../entities/LanguagePack.php';

class LocalizationService {
    private string $langDir;
    private string $currentLang;
    private array $translations = [];

    public function __construct(string $langDir, string $defaultLang = 'sk') {
        $this->langDir     = $langDir;
        $this->currentLang = $defaultLang;
        $this->loadLanguage($defaultLang);
    }

    public function loadLanguage(string $code): bool {
        $file = $this->langDir . "/$code.json";
        if (!file_exists($file)) return false;
        $this->translations = json_decode(file_get_contents($file), true) ?? [];
        $this->currentLang  = $code;
        return true;
    }

    public function translate(string $key): string {
        return $this->translations[$key] ?? $key;
    }

    public function setLanguage(string $code): void {
        $this->loadLanguage($code);
    }

    public function getCurrentLanguage(): string {
        return $this->currentLang;
    }

    public function getAvailableLanguages(): array {
        $langs = [];
        foreach (glob($this->langDir . '/*.json') as $file) {
            $langs[] = basename($file, '.json');
        }
        return $langs;
    }
}
