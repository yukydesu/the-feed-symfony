<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table utilisateur et ajout de la relation auteur dans publication';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Suppression des anciennes publications (sans auteur)
        $this->addSql('DELETE FROM publication');

        // Création de la table utilisateur si elle n'existe pas déjà
        $this->addSql('CREATE TABLE IF NOT EXISTS utilisateur (id INT AUTO_INCREMENT NOT NULL, login VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, adresse_email VARCHAR(255) NOT NULL, nom_photo_profil LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_LOGIN (login), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (adresse_email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Vérifier si la colonne auteur_id existe déjà
        $columnExists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'publication'
             AND COLUMN_NAME = 'auteur_id'"
        );

        if (!$columnExists) {
            // Ajout de la colonne auteur_id
            $this->addSql('ALTER TABLE publication ADD auteur_id INT NOT NULL');
            $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C677960BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
            $this->addSql('CREATE INDEX IDX_AF3C677960BB6FE6 ON publication (auteur_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C677960BB6FE6');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP INDEX IDX_AF3C677960BB6FE6 ON publication');
        $this->addSql('ALTER TABLE publication DROP auteur_id');
    }
}
