-- =====================================================================
--  europe.transports - Portail de réservation de billets de TGV
--  Projet Agile 3AE (ECAM-EPMI) - Enseignant : L. TRAN
--
--  schema.sql : création de la base et des tables (MySQL 8 / MariaDB 10+)
--  Ordre d'exécution : 1) schema.sql  2) seed.sql
-- =====================================================================

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS europe_transports;
CREATE DATABASE europe_transports CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE europe_transports;

-- ---------------------------------------------------------------------
-- GARE : les gares desservies (Paris Gare de Lyon, Lyon Part-Dieu...)
-- ---------------------------------------------------------------------
CREATE TABLE gare (
    id_gare   INT AUTO_INCREMENT PRIMARY KEY,
    nom       VARCHAR(80)  NOT NULL UNIQUE,
    ville     VARCHAR(60)  NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- VOYAGE : une circulation de TGV à une date et une heure données.
-- Le prix dépend de la date et de l'heure : c'est ce qui alimente
-- le « Calendrier des prix » (Epic 1 - US3).
-- ---------------------------------------------------------------------
CREATE TABLE voyage (
    id_voyage        INT AUTO_INCREMENT PRIMARY KEY,
    numero_train     VARCHAR(10)  NOT NULL,
    id_gare_depart   INT          NOT NULL,
    id_gare_arrivee  INT          NOT NULL,
    date_depart      DATE         NOT NULL,
    heure_depart     TIME         NOT NULL,
    heure_arrivee    TIME         NOT NULL,
    prix             DECIMAL(6,2) NOT NULL,
    places_dispo     INT          NOT NULL DEFAULT 300,
    CONSTRAINT fk_voyage_depart  FOREIGN KEY (id_gare_depart)  REFERENCES gare(id_gare),
    CONSTRAINT fk_voyage_arrivee FOREIGN KEY (id_gare_arrivee) REFERENCES gare(id_gare),
    CONSTRAINT chk_voyage_gares  CHECK (id_gare_depart <> id_gare_arrivee),
    CONSTRAINT chk_voyage_prix   CHECK (prix > 0),
    -- index utilisé par toutes les recherches (trajet + date)
    INDEX idx_voyage_recherche (id_gare_depart, id_gare_arrivee, date_depart)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- OPTION_VOYAGE : options payantes (place tranquille, prise, bagage,
-- SMS, garantie annulation) - Epic 1 - US5
-- ---------------------------------------------------------------------
CREATE TABLE option_voyage (
    id_option  INT AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(20)  NOT NULL UNIQUE,
    libelle    VARCHAR(120) NOT NULL,
    prix       DECIMAL(6,2) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- UTILISATEUR : comptes gérés par l'administrateur (Epic 5).
--   role = 'admin'    -> administrateur système (TS1)
--   role = 'adherent' -> voyageur titulaire d'une carte d'abonnement
-- Les mots de passe sont stockés hachés (password_hash, bcrypt).
-- nb_echecs / bloque_jusqu : protection contre les essais répétés (TS2).
-- ---------------------------------------------------------------------
CREATE TABLE utilisateur (
    id_utilisateur  INT AUTO_INCREMENT PRIMARY KEY,
    login           VARCHAR(50)  NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255) NOT NULL,
    role            ENUM('admin','adherent') NOT NULL DEFAULT 'adherent',
    civilite        ENUM('M','Mme') NULL,
    nom             VARCHAR(60)  NOT NULL,
    prenom          VARCHAR(60)  NOT NULL,
    email           VARCHAR(120) NOT NULL,
    numero_carte    VARCHAR(20)  NULL UNIQUE,   -- carte d'abonnement (adhérents)
    date_fin_carte  DATE         NULL,
    nb_echecs       INT          NOT NULL DEFAULT 0,
    bloque_jusqu    DATETIME     NULL,
    date_creation   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CODE_PROMO : codes de réduction (-10 € pour les adhérents) - Epic 4
-- ---------------------------------------------------------------------
CREATE TABLE code_promo (
    code               VARCHAR(20)  PRIMARY KEY,
    montant_reduction  DECIMAL(6,2) NOT NULL,
    reserve_adherents  BOOLEAN      NOT NULL DEFAULT TRUE,
    date_fin           DATE         NOT NULL,
    actif              BOOLEAN      NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CLIENT : le voyageur qui achète (Epic 3 - US1)
-- ---------------------------------------------------------------------
CREATE TABLE client (
    id_client   INT AUTO_INCREMENT PRIMARY KEY,
    civilite    ENUM('M','Mme') NOT NULL,
    nom         VARCHAR(60)  NOT NULL,
    prenom      VARCHAR(60)  NOT NULL,
    telephone   VARCHAR(20)  NOT NULL,
    email       VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RESERVATION : une commande payée (le panier validé).
-- Sécurité : on ne stocke JAMAIS le numéro complet de la carte bancaire
-- ni le cryptogramme, seulement les 4 derniers chiffres (règles PCI-DSS).
-- ---------------------------------------------------------------------
CREATE TABLE reservation (
    id_reservation    INT AUTO_INCREMENT PRIMARY KEY,
    reference         VARCHAR(12)  NOT NULL UNIQUE,
    date_reservation  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_client         INT          NOT NULL,
    id_utilisateur    INT          NULL,          -- adhérent (carte d'abonnement)
    code_promo        VARCHAR(20)  NULL,
    montant_brut      DECIMAL(8,2) NOT NULL,
    remise            DECIMAL(8,2) NOT NULL DEFAULT 0,
    montant_total     DECIMAL(8,2) NOT NULL,
    cb_4_derniers     CHAR(4)      NOT NULL,
    statut            ENUM('payee','annulee') NOT NULL DEFAULT 'payee',
    CONSTRAINT fk_resa_client  FOREIGN KEY (id_client)      REFERENCES client(id_client),
    CONSTRAINT fk_resa_adh     FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE SET NULL,
    CONSTRAINT fk_resa_promo   FOREIGN KEY (code_promo)     REFERENCES code_promo(code)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- BILLET : un billet par trajet (ALLER / RETOUR) d'une réservation
-- ---------------------------------------------------------------------
CREATE TABLE billet (
    id_billet       INT AUTO_INCREMENT PRIMARY KEY,
    numero_billet   VARCHAR(16)  NOT NULL UNIQUE,
    id_reservation  INT          NOT NULL,
    id_voyage       INT          NOT NULL,
    sens            ENUM('ALLER','RETOUR') NOT NULL,
    prix            DECIMAL(6,2) NOT NULL,
    voiture         TINYINT      NOT NULL,
    place           SMALLINT     NOT NULL,
    CONSTRAINT fk_billet_resa   FOREIGN KEY (id_reservation) REFERENCES reservation(id_reservation) ON DELETE CASCADE,
    CONSTRAINT fk_billet_voyage FOREIGN KEY (id_voyage)      REFERENCES voyage(id_voyage)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RESERVATION_OPTION : association « une réservation contient des options »
-- (prix_applique = prix au moment de l'achat, même si le tarif change)
-- ---------------------------------------------------------------------
CREATE TABLE reservation_option (
    id_reservation  INT          NOT NULL,
    id_option       INT          NOT NULL,
    prix_applique   DECIMAL(6,2) NOT NULL,
    PRIMARY KEY (id_reservation, id_option),
    CONSTRAINT fk_ro_resa   FOREIGN KEY (id_reservation) REFERENCES reservation(id_reservation) ON DELETE CASCADE,
    CONSTRAINT fk_ro_option FOREIGN KEY (id_option)      REFERENCES option_voyage(id_option)
) ENGINE=InnoDB;
