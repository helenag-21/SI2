<?php
class Backup {
    public ?int $id;
    public ?int $diaryId;
    public string $title;
    public string $data;
    public string $createdAt;

    public function __construct(?int $diaryId, string $title, string $data, ?int $id=null, string $createdAt='') {
        $this->id=$id; $this->diaryId=$diaryId; $this->title=$title; $this->data=$data;
        $this->createdAt=$createdAt?:date('Y-m-d H:i:s');
    }

    public static function fromArray(array $row): self {
        return new self(isset($row['FK_ID_dennik'])?(int)$row['FK_ID_dennik']:null,
            $row['nazov'], $row['balik_dat'], (int)$row['PK_ID_zaloha'], $row['datum_vytvorenia']??'');
    }
}
