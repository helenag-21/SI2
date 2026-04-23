SET NAMES utf8mb4;
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS zabezpecenie (
    PK_ID_zabezpecenie  INT AUTO_INCREMENT PRIMARY KEY,
    FK_ID_pouzivatel    INT          NULL,
    typ_zabezpecenia    ENUM('password','biometric') NOT NULL DEFAULT 'password',
    hash_hesla          VARCHAR(255) NULL,
    datum_upravy        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pouzivatel (
    PK_ID_pouzivatel    INT AUTO_INCREMENT PRIMARY KEY,
    FK_ID_zabezpecenie  INT          NULL,
    meno                VARCHAR(50)  NOT NULL,
    priezvisko          VARCHAR(50)  NOT NULL,
    email               VARCHAR(100) NOT NULL UNIQUE,
    CONSTRAINT fk_pou_zab FOREIGN KEY (FK_ID_zabezpecenie)
        REFERENCES zabezpecenie(PK_ID_zabezpecenie) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE zabezpecenie
    ADD CONSTRAINT fk_zab_pou FOREIGN KEY (FK_ID_pouzivatel)
        REFERENCES pouzivatel(PK_ID_pouzivatel) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS Dennik (
    PK_ID_dennik        INT AUTO_INCREMENT PRIMARY KEY,
    FK_ID_pouzivatel    INT          NOT NULL,
    FK_ID_zabezpecenie  INT          NULL,
    nazov               VARCHAR(200) NOT NULL,
    datum_vytvorenia    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datum_upravy        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_den_pou FOREIGN KEY (FK_ID_pouzivatel)
        REFERENCES pouzivatel(PK_ID_pouzivatel) ON DELETE CASCADE,
    CONSTRAINT fk_den_zab FOREIGN KEY (FK_ID_zabezpecenie)
        REFERENCES zabezpecenie(PK_ID_zabezpecenie) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Kategoria (
    PK_ID_kategoria     INT AUTO_INCREMENT PRIMARY KEY,
    FK_ID_pouzivatel    INT          NOT NULL,
    nazov               VARCHAR(50)  NOT NULL,
    typ                 ENUM('system','custom') NOT NULL DEFAULT 'custom',
    datum_vytvorenia    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kat_pou FOREIGN KEY (FK_ID_pouzivatel)
        REFERENCES pouzivatel(PK_ID_pouzivatel) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Sablona (
    PK_ID_sablona       INT AUTO_INCREMENT PRIMARY KEY,
    FK_ID_pouzivatel    INT          NOT NULL,
    nazov               VARCHAR(50)  NOT NULL,
    struktura           TEXT         NOT NULL,
    datum_vytvorenia    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sab_pou FOREIGN KEY (FK_ID_pouzivatel)
        REFERENCES pouzivatel(PK_ID_pouzivatel) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Zapis (
    PK_ID_zapis         INT AUTO_INCREMENT PRIMARY KEY,
    FK_ID_dennik        INT          NOT NULL,
    FK_ID_zabezpecenie  INT          NULL,
    FK_ID_kategoria     INT          NULL,
    FK_ID_sablona       INT          NULL,
    nazov               VARCHAR(200) NOT NULL DEFAULT '',
    obsah               LONGTEXT     NOT NULL,
    datum_vytvorenia    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datum_upravy        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_zap_den FOREIGN KEY (FK_ID_dennik)
        REFERENCES Dennik(PK_ID_dennik) ON DELETE CASCADE,
    CONSTRAINT fk_zap_zab FOREIGN KEY (FK_ID_zabezpecenie)
        REFERENCES zabezpecenie(PK_ID_zabezpecenie) ON DELETE SET NULL,
    CONSTRAINT fk_zap_kat FOREIGN KEY (FK_ID_kategoria)
        REFERENCES Kategoria(PK_ID_kategoria) ON DELETE SET NULL,
    CONSTRAINT fk_zap_sab FOREIGN KEY (FK_ID_sablona)
        REFERENCES Sablona(PK_ID_sablona) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Priloha (
    PK_ID_priloha       INT AUTO_INCREMENT PRIMARY KEY,
    FK_ID_zapis         INT          NOT NULL,
    nazov_suboru        VARCHAR(255) NOT NULL,
    typ_suboru          VARCHAR(100) NOT NULL DEFAULT '',
    velkost             BIGINT       NOT NULL DEFAULT 0,
    cesta_suboru        VARCHAR(500) NOT NULL DEFAULT '',
    datum_pridania      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pri_zap FOREIGN KEY (FK_ID_zapis)
        REFERENCES Zapis(PK_ID_zapis) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Zaloha (
    PK_ID_zaloha        INT AUTO_INCREMENT PRIMARY KEY,
    FK_ID_dennik        INT          NOT NULL,
    nazov               VARCHAR(200) NOT NULL,
    balik_dat           LONGTEXT     NOT NULL,
    datum_vytvorenia    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_zal_den FOREIGN KEY (FK_ID_dennik)
        REFERENCES Dennik(PK_ID_dennik) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
