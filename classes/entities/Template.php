<?php
class Template {
    public ?int $id;
    public int $userId;
    public string $title;
    public string $structure;
    public string $createdAt;

    public function __construct(int $userId, string $title, string $structure, ?int $id=null, string $createdAt='') {
        $this->id=$id; $this->userId=$userId; $this->title=$title; $this->structure=$structure;
        $this->createdAt=$createdAt?:date('Y-m-d H:i:s');
    }

    public static function fromArray(array $row): self {
        return new self((int)$row['FK_ID_pouzivatel'], $row['nazov'], $row['struktura'],
            (int)$row['PK_ID_sablona'], $row['datum_vytvorenia']??'');
    }
}
