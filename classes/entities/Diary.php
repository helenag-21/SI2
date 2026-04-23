<?php
class Diary {
    public ?int $id;
    public int $userId;
    public ?int $securityId;
    public string $title;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(int $userId, string $title, ?int $securityId=null, ?int $id=null, string $createdAt='', string $updatedAt='') {
        $this->id=$id; $this->userId=$userId; $this->title=$title; $this->securityId=$securityId;
        $this->createdAt=$createdAt?:date('Y-m-d H:i:s'); $this->updatedAt=$updatedAt?:date('Y-m-d H:i:s');
    }

    public static function fromArray(array $row): self {
        return new self((int)$row['FK_ID_pouzivatel'], $row['nazov'],
            isset($row['FK_ID_zabezpecenie'])?(int)$row['FK_ID_zabezpecenie']:null,
            (int)$row['PK_ID_dennik'], $row['datum_vytvorenia']??'', $row['datum_upravy']??'');
    }
}
