<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251219155521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` CHANGE total_price total_price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE `order` RENAME INDEX idx_f5299398a76ed395 TO IDX_34E8BC9CA76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `Order` CHANGE total_price total_price INT NOT NULL');
        $this->addSql('ALTER TABLE `Order` RENAME INDEX idx_34e8bc9ca76ed395 TO IDX_F5299398A76ED395');
    }
}
