<?php
require_once __DIR__ . '/../entities/Booking.php';

class BookingService
{
    public function save(Booking $booking): int
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                INSERT INTO bookings (user_id, studio_id, package_id, date, start_time, end_time, total_price, status, notes)
                VALUES (:user_id, :studio_id, :package_id, :date, :start_time, :end_time, :total_price, :status, :notes)
            ");
            $req->execute([
                ':user_id'     => $booking->getUserId(),
                ':studio_id'   => $booking->getStudioId(),
                ':package_id'  => $booking->getPackageId(),
                ':date'        => $booking->getDate(),
                ':start_time'  => $booking->getStartTime(),
                ':end_time'    => $booking->getEndTime(),
                ':total_price' => $booking->getTotalPrice(),
                ':status'      => $booking->getStatus(),
                ':notes'       => $booking->getNotes(),
            ]);
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error saving booking: " . $e->getMessage());
            return 0;
        }
    }

    public function update(Booking $booking): bool
    {
        try {
            $pdo  = Database::getConnection();
            $req = $pdo->prepare("
                UPDATE bookings
                SET user_id = :user_id, studio_id = :studio_id, package_id = :package_id,
                    date = :date, start_time = :start_time, end_time = :end_time,
                    total_price = :total_price, status = :status, notes = :notes
                WHERE id = :id
            ");
            return $req->execute([
                ':user_id'     => $booking->getUserId(),
                ':studio_id'   => $booking->getStudioId(),
                ':package_id'  => $booking->getPackageId(),
                ':date'        => $booking->getDate(),
                ':start_time'  => $booking->getStartTime(),
                ':end_time'    => $booking->getEndTime(),
                ':total_price' => $booking->getTotalPrice(),
                ':status'      => $booking->getStatus(),
                ':notes'       => $booking->getNotes(),
                ':id'          => $booking->getId(),
            ]);
        } catch (PDOException $e) {
            error_log("Error updating booking: " . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            $req = Database::getConnection()->prepare("DELETE FROM bookings WHERE id = :id");
            return $req->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting booking: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Booking
    {
        try {
            $req = Database::getConnection()->prepare("SELECT * FROM bookings WHERE id = :id");
            $req->execute([':id' => $id]);
            $row = $req->fetch();
            return $row ? Booking::fromRow($row) : null;
        } catch (PDOException $e) {
            error_log("Error finding booking: " . $e->getMessage());
            return null;
        }
    }

    public static function findByUser(int $user_id): array
    {
        try {
            $req = Database::getConnection()->prepare("
                SELECT * FROM bookings WHERE user_id = :user_id ORDER BY date DESC
            ");
            $req->execute([':user_id' => $user_id]);
            return array_map([Booking::class, 'fromRow'], $req->fetchAll());
        } catch (PDOException $e) {
            error_log("Error finding bookings for user: " . $e->getMessage());
            return [];
        }
    }

    public static function findByStudio(int $studio_id): array
    {
        try {
            $req = Database::getConnection()->prepare("
                SELECT * FROM bookings WHERE studio_id = :studio_id ORDER BY date DESC
            ");
            $req->execute([':studio_id' => $studio_id]);
            return array_map([Booking::class, 'fromRow'], $req->fetchAll());
        } catch (PDOException $e) {
            error_log("Error finding bookings for studio: " . $e->getMessage());
            return [];
        }
    }

    public static function findAll(): array
    {
        try {
            $req = Database::getConnection()->query("
                SELECT b.*, u.username, s.name AS studio_name, p.name AS package_name
                FROM bookings b
                JOIN users   u ON b.user_id   = u.id
                JOIN studios s ON b.studio_id = s.id
                LEFT JOIN packages p ON b.package_id = p.id
                ORDER BY b.date DESC
            ");
            return array_map([Booking::class, 'fromRow'], $req->fetchAll());
        } catch (PDOException $e) {
            error_log("Error finding all bookings: " . $e->getMessage());
            return [];
        }
    }

    // Check if a studio is already booked for a given date/time slot
    public static function isSlotTaken(
        int $studio_id,
        string $date,
        string $start,
        string $end,
        ?int $exclude_id = null
    ): bool {
        $sql = "
            SELECT COUNT(*) FROM bookings
            WHERE studio_id = :studio_id
              AND date = :date
              AND status != :status_canceled
              AND start_time < :end_time
              AND end_time   > :start_time
        ";
        $params = [
            ':studio_id'  => $studio_id,
            ':date'       => $date,
            ':start_time' => $start,
            ':end_time'   => $end,
            ':status_canceled' => Status::Canceled->value,
        ];
        if ($exclude_id) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $exclude_id;
        }

        $req = Database::getConnection()->prepare($sql);
        $req->execute($params);
        return (int) $req->fetchColumn() > 0;
    }
}
