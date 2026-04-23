<?php
class Category {
    public ?int $id;
    public int $userId;
    public string $title;
    public string $type;
    public string $createdAt;

    public function __construct(int $userId, string $title, string $type='custom', ?int $id=null, string $createdAt='') {
        $this->id=$id; $this->userId=$userId; $this->title=$title; $this->type=$type;
        $this->createdAt=$createdAt?:date('Y-m-d H:i:s');
    }

    public static function fromArray(array $row): self {
        return new self((int)$row['FK_ID_pouzivatel'], $row['nazov'], $row['typ']??'custom',
            (int)$row['PK_ID_kategoria'], $row['datum_vytvorenia']??'');
    }
}
