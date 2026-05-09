<?php
require_once '../entities/Studio.php';

class StudioService
{
    public function save(Studio $studio): int
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                INSERT INTO studios (name, description, location, capacity, price_per_hour, cover_image, status)
                VALUES (:name, :description, :location, :capacity, :price_per_hour, :cover_image, :status)
            ");
            $req->execute([
                ':name'           => $studio->getName(),
                ':description'    => $studio->getDescription(),
                ':location'       => $studio->getLocation(),
                ':capacity'       => $studio->getCapacity(),
                ':price_per_hour' => $studio->getPricePerHour(),
                ':cover_image'    => $studio->getCoverImage(),
                ':status'         => $studio->getStatus(),
            ]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Failed to save studio. Please try again later.");
            error_log("Database error: " . $e->getMessage());
            return 0;
        }
    }

    public function update(Studio $studio): bool
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                UPDATE studios
                SET name = :name, description = :description, location = :location,
                    capacity = :capacity, price_per_hour = :price_per_hour,
                    cover_image = :cover_image, status = :status
                WHERE id = :id
            ");
            return $req->execute([
                ':name'           => $studio->getName(),
                ':description'    => $studio->getDescription(),
                ':location'       => $studio->getLocation(),
                ':capacity'       => $studio->getCapacity(),
                ':price_per_hour' => $studio->getPricePerHour(),
                ':cover_image'    => $studio->getCoverImage(),
                ':status'         => $studio->getStatus(),
                ':id'             => $studio->getId(),
            ]);
        } catch (PDOException $e) {
            error_log("Failed to update studio. Please try again later.");
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            $req = Database::getConnection()->prepare("DELETE FROM studios WHERE id = :id");
            return $req->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Failed to delete studio. Please try again later.");
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Studio
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM studios WHERE id = :id");
            $req->execute([':id' => $id]);
            $row = $req->fetch();
            return $row ? Studio::fromRow($row) : null;
        } catch (PDOException $e) {
            error_log("Failed to find studio. Please try again later.");
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function findAll(): array
    {
        try {
            $req = Database::getConnection()->query("SELECT * FROM studios ORDER BY created_at DESC");
            return array_map([Studio::class, 'fromRow'], $req->fetchAll());
        } catch (PDOException $e) {
            error_log("Failed to find studios. Please try again later.");
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public static function findAvailable(): array
    {
        try {
            $req = Database::getConnection()->query("SELECT * FROM studios WHERE status = 'available' ORDER BY price_per_hour ASC");
            return array_map([Studio::class, 'fromRow'], $req->fetchAll());
        } catch (PDOException $e) {
            error_log("Failed to find available studios. Please try again later.");
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }
}
