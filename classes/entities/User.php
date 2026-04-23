<?php
class User {
    public ?int $id;
    public string $firstName;
    public string $lastName;
    public string $email;
    public ?int $securityId;

    public function __construct(string $firstName, string $lastName, string $email, ?int $securityId=null, ?int $id=null) {
        $this->id=$id; $this->firstName=$firstName; $this->lastName=$lastName;
        $this->email=$email; $this->securityId=$securityId;
    }

    public static function fromArray(array $row): self {
        return new self($row['meno'], $row['priezvisko'], $row['email'],
            isset($row['FK_ID_zabezpecenie'])?(int)$row['FK_ID_zabezpecenie']:null,
            (int)$row['PK_ID_pouzivatel']);
    }
}
