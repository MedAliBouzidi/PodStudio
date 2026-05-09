<?php

require_once '../entities/Client.php';
require_once '../config/Database.php';


class ClientService
{
    public function save(Client $client): int
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                INSERT INTO clients (full_name, username, email, password, phone, profile_picture)
                VALUES (:full_name, :username, :email, :password, :phone, :profile_picture)
            ");
            $req->execute([
                ':full_name'       => $client->getFullName(),
                ':username'        => $client->getUsername(),
                ':email'           => $client->getEmail(),
                ':password'        => md5($client->getPassword()),
                ':profile_picture' => $client->getProfilePicture(),
                ':phone'           => $client->getPhone(),
            ]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return 0;
        }
    }

    public function update(Client $client): bool
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                UPDATE clients
                SET full_name = :full_name, username = :username, email = :email, phone = :phone, profile_picture = :profile_picture
                WHERE id = :id
            ");
            return $req->execute([
                ':full_name'       => $client->getFullName(),
                ':username'        => $client->getUsername(),
                ':email'           => $client->getEmail(),
                ':profile_picture' => $client->getProfilePicture(),
                ':phone'           => $client->getPhone(),
                ':id'              => $client->getId(),
            ]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            $req = Database::getConnection()->prepare("DELETE FROM clients WHERE id = :id");
            return $req->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Client
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM clients WHERE id = :id");
            $req->execute([':id' => $id]);
            $row = $req->fetch();
            return $row ? Client::fromRow($row) : null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function findByEmail(string $email): ?Client
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM clients WHERE email = :email");
            $req->execute([':email' => $email]);
            $row = $req->fetch();
            return $row ? Client::fromRow($row) : null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function findAll(): array
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM clients ORDER BY created_at DESC");
            $req->execute();
            return array_map([Client::class, 'fromRow'], $req->fetchAll());
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public static function login(string $email, string $password): ?Client
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
