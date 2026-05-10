<?php
require_once __DIR__ . '/../entities/Equipment.php';

class EquipmentService
{
    public static function save(Equipment $equipment): int
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                INSERT INTO equipments (studio_id, name, brand, description, image, quantity, status)
                VALUES (:studio_id, :name, :brand, :description, :image, :quantity, :status)
            ");
            $req->execute([
                ':studio_id'   => $equipment->getStudioId(),
                ':name'        => $equipment->getName(),
                ':brand'       => $equipment->getBrand(),
                ':description' => $equipment->getDescription(),
                ':image'       => $equipment->getImage(),
                ':quantity'    => $equipment->getQuantity(),
                ':status'      => $equipment->getStatus()->value,
            ]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return -1;
        }
    }

    public static function update(Equipment $equipment): bool
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                UPDATE equipments
                SET studio_id = :studio_id, name = :name, brand = :brand,
                    description = :description, image = :image,
                    quantity = :quantity, status = :status
                WHERE id = :id
            ");
            return $req->execute([
                ':studio_id'   => $equipment->getStudioId(),
                ':name'        => $equipment->getName(),
                ':brand'       => $equipment->getBrand(),
                ':description' => $equipment->getDescription(),
                ':image'       => $equipment->getImage(),
                ':quantity'    => $equipment->getQuantity(),
                ':status'      => $equipment->getStatus()->value,
                ':id'          => $equipment->getId(),
            ]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("DELETE FROM equipments WHERE id = :id");
            return $req->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Equipment
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM equipments WHERE id = :id");
            $req->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
        $row = $req->fetch();
        return $row ? Equipment::fromRow($row) : null;
    }

    public static function findByStudio(int $studio_id): array
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM equipments WHERE studio_id = :studio_id");
            $req->execute([':studio_id' => $studio_id]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
        return array_map([Equipment::class, 'fromRow'], $req->fetchAll());
    }

    public static function findAll(): array
    {
        try {
            $req = Database::getConnection()->query("SELECT * FROM equipments ORDER BY studio_id, name");
            return array_map([Equipment::class, 'fromRow'], $req->fetchAll());
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }
}
