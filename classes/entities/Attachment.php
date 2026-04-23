<?php
class Attachment {
    public ?int $id;
    public int $entryId;
    public string $fileName;
    public string $fileType;
    public int $fileSize;
    public string $filePath;
    public string $createdAt;

    public function __construct(int $entryId, string $fileName, string $fileType, int $fileSize, string $filePath, ?int $id=null, string $createdAt='') {
        $this->id=$id; $this->entryId=$entryId; $this->fileName=$fileName;
        $this->fileType=$fileType; $this->fileSize=$fileSize; $this->filePath=$filePath;
        $this->createdAt=$createdAt?:date('Y-m-d H:i:s');
    }

    public static function fromArray(array $row): self {
        return new self((int)$row['FK_ID_zapis'], $row['nazov_suboru'], $row['typ_suboru'],
            (int)$row['velkost'], $row['cesta_suboru'], (int)$row['PK_ID_priloha'], $row['datum_pridania']??'');
    }
}
