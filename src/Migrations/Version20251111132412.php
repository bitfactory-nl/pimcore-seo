<?php

declare(strict_types=1);

namespace SeoBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251111132412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert php-serialized seo_element_meta_data.data to JSON';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->connection;

        $rows = $connection->fetchAllAssociative('SELECT id, data FROM seo_element_meta_data');

        foreach ($rows as $row) {
            $data = $row['data'];

            if ($data === null || $data === '') {
                continue;
            }

            $decoded = @unserialize($data);
            if (is_array($decoded)) {
                $json = json_encode($decoded, JSON_UNESCAPED_UNICODE);

                $connection->update(
                    'seo_element_meta_data',
                    ['data' => $json],
                    ['id' => $row['id']]
                );
                $this->write("Converted row ID {$row['id']} to JSON");
            } else {
                // Already JSON or invalid, skip
                $this->write("Skipped ID {$row['id']} — not PHP serialized");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $connection = $this->connection;

        // Optional: convert back from JSON to PHP-serialized array
        $rows = $connection->fetchAllAssociative('SELECT id, data FROM seo_element_meta_data');

        foreach ($rows as $row) {
            $data = $row['data'];

            if ($data === null || $data === '') {
                continue;
            }

            $decoded = json_decode($data, true);

            if (is_array($decoded)) {
                $serialized = serialize($decoded);
                $connection->update(
                    'seo_element_meta_data',
                    ['data' => $serialized],
                    ['id' => $row['id']]
                );
                $this->write("🔁 Reverted row ID {$row['id']} to PHP serialized format");
            }
        }
    }
}
