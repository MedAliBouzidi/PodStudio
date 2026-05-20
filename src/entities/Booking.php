<?php

require_once __DIR__ . '/../services/ClientService.php';
require_once __DIR__ . '/../services/StudioService.php';
require_once __DIR__ . '/../services/PackageService.php';

class Booking
{
    private ?int    $id;
    private int     $user_id;
    private int     $studio_id;
    private ?int    $package_id;
    private string  $date;
    private string  $start_time;
    private string  $end_time;
    private float   $total_price;
    private string  $status;
    private ?string $notes;
    private ?string $created_at;

    public function __construct(
        int     $user_id,
        int     $studio_id,
        string  $date,
        string  $start_time,
        string  $end_time,
        float   $total_price,
        ?int    $package_id,
        string  $status,
        ?string $notes
    ) {
        $this->user_id     = $user_id;
        $this->studio_id   = $studio_id;
        $this->package_id  = $package_id;
        $this->date        = $date;
        $this->start_time  = $start_time;
        $this->end_time    = $end_time;
        $this->total_price = $total_price;
        $this->status      = $status ?? Status::Pending;
        $this->notes       = $notes;
        $this->created_at  = date('Y-m-d H:i:s');
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUserId(): int
    {
        return $this->user_id;
    }
    public function getStudioId(): int
    {
        return $this->studio_id;
    }
    public function getPackageId(): ?int
    {
        return $this->package_id;
    }
    public function getDate(): string
    {
        return $this->date;
    }
    public function getStartTime(): string
    {
        return $this->start_time;
    }
    public function getEndTime(): string
    {
        return $this->end_time;
    }
    public function getTotalPrice(): float
    {
        return $this->total_price;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getNotes(): ?string
    {
        return $this->notes;
    }
    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    // Setters
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }
    public function setStudioId(int $studio_id): void
    {
        $this->studio_id = $studio_id;
    }
    public function setPackageId(?int $package_id): void
    {
        $this->package_id = $package_id;
    }
    public function setDate(string $date): void
    {
        $this->date = $date;
    }
    public function setStartTime(string $start): void
    {
        $this->start_time = $start;
    }
    public function setEndTime(string $end): void
    {
        $this->end_time = $end;
    }
    public function setTotalPrice(float $price): void
    {
        $this->total_price = $price;
    }
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public static function fromRow(array $row): Booking
    {
        $booking = new Booking(
            (int)   $row['user_id'],
            (int)   $row['studio_id'],
            $row['date'],
            $row['start_time'],
            $row['end_time'],
            (float) $row['total_price'],
            isset($row['package_id']) ? (int) $row['package_id'] : null,
            $row['status'],
            $row['notes']
        );
        $booking->id = (int) $row['id'];
        $booking->created_at = $row['created_at'];
        return $booking;
    }

    public function getUser(): ?User
    {
        return ClientService::findById($this->user_id);
    }

    public function getStudio(): ?Studio
    {
        return StudioService::findById($this->studio_id);
    }

    public function getPackage(): ?Package
    {
        if ($this->package_id === null) 
            return null;
        return PackageService::findById($this->package_id);
    }
}
