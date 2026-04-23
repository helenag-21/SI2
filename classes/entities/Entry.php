<?php
class Entry {
    public ?int $id;
    public int $diaryId;
    public ?int $securityId;
    public ?int $categoryId;
    public ?int $templateId;
    public string $title;
    public string $content;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(int $diaryId, string $title, string $content, ?int $categoryId=null, ?int $templateId=null, ?int $securityId=null, ?int $id=null, string $createdAt='', string $updatedAt='') {
        $this->id=$id; $this->diaryId=$diaryId; $this->title=$title; $this->content=$content;
        $this->categoryId=$categoryId; $this->templateId=$templateId; $this->securityId=$securityId;
        $this->createdAt=$createdAt?:date('Y-m-d H:i:s'); $this->updatedAt=$updatedAt?:date('Y-m-d H:i:s');
    }

    public static function fromArray(array $row): self {
        return new self((int)$row['FK_ID_dennik'], $row['nazov']??'', $row['obsah']??'',
            isset($row['FK_ID_kategoria'])?(int)$row['FK_ID_kategoria']:null,
            isset($row['FK_ID_sablona'])?(int)$row['FK_ID_sablona']:null,
            isset($row['FK_ID_zabezpecenie'])?(int)$row['FK_ID_zabezpecenie']:null,
            (int)$row['PK_ID_zapis'], $row['datum_vytvorenia']??'', $row['datum_upravy']??'');
    }
}
