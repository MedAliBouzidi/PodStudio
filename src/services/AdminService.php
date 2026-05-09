<?php

require_once __DIR__ . '/../entities/Admin.php';
require_once __DIR__ . '/../config/Database.php';


class AdminService
{
    public function save(Admin $admin): int
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                INSERT INTO admins (full_name, username, email, password, profile_picture)
                VALUES (:full_name, :username, :email, :password, :profile_picture)
            ");
            $req->execute([
                ':full_name'       => $admin->getFullName(),
                ':username'        => $admin->getUsername(),
                ':email'           => $admin->getEmail(),
                ':password'        => md5($admin->getPassword()),
                ':profile_picture' => $admin->getProfilePicture(),
            ]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return 0;
        }
    }

    public function update(Admin $admin): bool
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                UPDATE admins
                SET full_name = :full_name, username = :username, email = :email, profile_picture = :profile_picture
                WHERE id = :id
            ");
            return $req->execute([
                ':full_name'       => $admin->getFullName(),
                ':username'        => $admin->getUsername(),
                ':email'           => $admin->getEmail(),
                ':profile_picture' => $admin->getProfilePicture(),
                ':id'              => $admin->getId(),
            ]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            $req = Database::getConnection()->prepare("DELETE FROM admins WHERE id = :id");
            return $req->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Admin
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM admins WHERE id = :id");
            $req->execute([':id' => $id]);
            $row = $req->fetch();
            return $row ? Admin::fromRow($row) : null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function findByEmail(string $email): ?Admin
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM admins WHERE email = :email");
            $req->execute([':email' => $email]);
            $row = $req->fetch();
            return $row ? Admin::fromRow($row) : null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function findAll(): array
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM admins ORDER BY created_at DESC");
            $req->execute();
            return array_map([Admin::class, 'fromRow'], $req->fetchAll());
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public static function login(string $email, string $password): ?Admin
    {
        try {
            $user = self::findByEmail($email);
            if ($user && md5($password) === $user->getPassword()) {
                return $user;
            }
            return null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }
}
