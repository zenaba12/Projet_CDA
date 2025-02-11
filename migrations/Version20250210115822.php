<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250210115822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, relation_id INT NOT NULL, numéro_suivi INT NOT NULL, date DATE NOT NULL, INDEX IDX_6EEAA67D3256915B (relation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande_product (commande_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_25F1760D82EA2E54 (commande_id), INDEX IDX_25F1760D4584665A (product_id), PRIMARY KEY(commande_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D3256915B FOREIGN KEY (relation_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commande_product ADD CONSTRAINT FK_25F1760D82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_product ADD CONSTRAINT FK_25F1760D4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D3256915B');
        $this->addSql('ALTER TABLE commande_product DROP FOREIGN KEY FK_25F1760D82EA2E54');
        $this->addSql('ALTER TABLE commande_product DROP FOREIGN KEY FK_25F1760D4584665A');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_product');
    }
}
