<?php
require_once '../config/Database.php';

class Equipment
{
    private ?int    $id;
    private int     $studio_id;
    private string  $name;
    private ?string $brand;
    private ?string $description;
    private string  $image;
    private int     $quantity;
    private Status  $status;

    public function __construct(
        int     $studio_id,
        string  $name,
        ?string $brand,
        ?string $description,
        string  $image,
        int     $quantity,
        Status  $status
    ) {
        $this->studio_id = $studio_id;
        $this->name = $name;
        $this->brand = $brand;
        $this->description = $description ?? "No description provided.";
        $this->image = $image ?? 'no_image.png';
        $this->quantity = $quantity ?? 1;
        $this->status = $status ?? Status::Available;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getStudioId(): int
    {
        return $this->studio_id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getBrand(): ?string
    {
        return $this->brand;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function getImage(): string
    {
        return $this->image;
    }
    public function getQuantity(): int
    {
        return $this->quantity;
    }
    public function getStatus(): Status
    {
        return $this->status;
    }

    // Setters
    public function setStudioId(int $studio_id): void
    {
        $this->studio_id = $studio_id;
    }
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function setBrand(?string $brand): void
    {
        $this->brand = $brand;
    }
    public function setDescription(?string $desc): void
    {
        $this->description = $desc;
    }
    public function setImage(string $image): void
    {
        $this->image = $image;
    }
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }
    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    public static function fromRow(array $row): Equipment
    {
        $equipment = new Equipment(
            $row['studio_id'],
            $row['name'],
            $row['brand'],
            $row['description'],
            $row['image'],
            $row['quantity'],
            Status::from($row['status'])
        );
        $equipment->id = $row['id'];
        return $equipment;
    }
}
