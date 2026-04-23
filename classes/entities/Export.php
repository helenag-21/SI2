<?php
class Export {
    public ?int $id;
    public int $diaryId;
    public string $format;
    public ?string $content;
    public ?string $filePath;
    public string $exportedAt;

    public function __construct(int $diaryId, string $format, ?string $content=null, ?string $filePath=null, ?int $id=null, string $exportedAt='') {
        $this->id=$id; $this->diaryId=$diaryId; $this->format=$format;
        $this->content=$content; $this->filePath=$filePath;
        $this->exportedAt=$exportedAt?:date('Y-m-d H:i:s');
    }

    public static function fromArray(array $row): self {
        return new self((int)$row['CK_ID_dennik'], $row['format'], $row['obsah']??null,
            $row['subor']??null, (int)$row['PK_ID_export'], $row['datum_exportu']??'');
    }
}
