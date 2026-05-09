<?php

require_once  '../config/Database.php';

class Studio
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private ?string $location;
    private int $capacity;
    private float $price_per_hour;
    private string $cover_image;
    private Status $status;
    private string $created_at;

    public function __construct(
        string  $name,
        int     $capacity,
        float   $price_per_hour,
        ?string $description,
        ?string $location,
        string  $cover_image,
        Status  $status
    ) {
        $this->name           = $name;
        $this->description    = $description ?? 'No description provided.';
        $this->location       = $location ?? 'Location not specified.';
        $this->capacity       = $capacity;
        $this->price_per_hour = $price_per_hour;
        $this->cover_image    = $cover_image ?? 'no_image.png';
        $this->status         = $status ?? Status::Available;
        $this->created_at     = date('Y-m-d H:i:s');
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function getLocation(): ?string
    {
        return $this->location;
    }
    public function getCapacity(): int
    {
        return $this->capacity;
    }
    public function getPricePerHour(): float
    {
        return $this->price_per_hour;
    }
    public function getCoverImage(): string
    {
        return $this->cover_image;
    }
    public function getStatus(): Status
    {
        return $this->status;
    }
    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    // Setters
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function setDescription(?string $desc): void
    {
        $this->description = $desc;
    }
    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }
    public function setCapacity(int $capacity): void
    {
        $this->capacity = $capacity;
    }
    public function setPricePerHour(float $price): void
    {
        $this->price_per_hour = $price;
    }
    public function setCoverImage(string $image): void
    {
        $this->cover_image = $image;
    }
    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }


    public static function fromRow(array $row): Studio
    {
        $studio = new Studio(
            $row['name'],
            (int)   $row['capacity'],
            (float) $row['price_per_hour'],
            $row['description'],
            $row['location'],
            $row['cover_image'],
            Status::from($row['status'])
        );
        $studio->id = (int) $row['id'];
        $studio->created_at = $row['created_at'];
        return $studio;
    }
}
