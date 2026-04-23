<?php
class Security {
    public ?int $id;
    public ?int $userId;
    public string $type;
    public ?string $passwordHash;
    public string $updatedAt;

    public function __construct(?int $userId, string $type='password', ?string $passwordHash=null, ?int $id=null, string $updatedAt='') {
        $this->id=$id; $this->userId=$userId; $this->type=$type;
        $this->passwordHash=$passwordHash; $this->updatedAt=$updatedAt?:date('Y-m-d H:i:s');
    }

    public static function fromArray(array $row): self {
        return new self(isset($row['FK_ID_pouzivatel'])?(int)$row['FK_ID_pouzivatel']:null,
            $row['typ_zabezpecenia']??'password', $row['hash_hesla']??null,
            (int)$row['PK_ID_zabezpecenie'], $row['datum_upravy']??'');
    }
}
