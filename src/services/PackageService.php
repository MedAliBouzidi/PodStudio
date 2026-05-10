<?php
require_once __DIR__ . '/../entities/Package.php';

class PackageService
{

    public static function save(Package $package): int
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                INSERT INTO packages (name, description, price, duration_hours, includes_equipment)
                VALUES (:name, :description, :price, :duration_hours, :includes_equipment)
            ");
            $req->execute([
                ':name'               => $package->getName(),
                ':description'        => $package->getDescription(),
                ':price'              => $package->getPrice(),
                ':duration_hours'     => $package->getDurationHours(),
                ':includes_equipment' => (int) $package->getIncludesEquipment(),
            ]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error saving package: " . $e->getMessage());
            return 0;
        }
    }

    public static function update(Package $package): bool
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                UPDATE packages
                SET name = :name, description = :description, price = :price,
                    duration_hours = :duration_hours, includes_equipment = :includes_equipment
                WHERE id = :id
            ");
            return $req->execute([
                ':name'               => $package->getName(),
                ':description'        => $package->getDescription(),
                ':price'              => $package->getPrice(),
                ':duration_hours'     => $package->getDurationHours(),
                ':includes_equipment' => (int) $package->getIncludesEquipment(),
                ':id'                 => $package->getId(),
            ]);
        } catch (PDOException $e) {
            error_log("Error updating package: " . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            $req = Database::getConnection()->prepare("DELETE FROM packages WHERE id = :id");
            return $req->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting package: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Package
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM packages WHERE id = :id");
            $req->execute([':id' => $id]);
            $row = $req->fetch();
            return $row ? Package::fromRow($row) : null;
        } catch (PDOException $e) {
            error_log("Error finding package by ID: " . $e->getMessage());
            return null;
        }
    }

    public static function findAll(): array
    {
        try {
            $req = Database::getConnection()->query("SELECT * FROM packages ORDER BY price ASC");
            return array_map([Package::class, 'fromRow'], $req->fetchAll());
        } catch (PDOException $e) {
            error_log("Error finding all packages: " . $e->getMessage());
            return [];
        }
    }
}
