<?php
class LanguagePack {
    public ?int $id;
    public string $code;
    public string $name;
    public array $translations;

    public function __construct(string $code, string $name, array $translations=[], ?int $id=null) {
        $this->id=$id; $this->code=$code; $this->name=$name; $this->translations=$translations;
    }

    public static function fromFile(string $code, string $name, string $filePath): self {
        $translations = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) ?? [] : [];
        return new self($code, $name, $translations);
    }

    public function translate(string $key): string {
        return $this->translations[$key] ?? $key;
    }
}
