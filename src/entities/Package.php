<?php

class Package
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private float $price;
    private int $duration_hours;
    private bool $includes_equipment;
    private string $created_at;

    public function __construct(
        string  $name,
        float   $price,
        int     $duration_hours,
        ?string $description,
        bool    $includes_equipment,
    ) {
        $this->name               = $name;
        $this->description        = $description ?? "No description provided.";
        $this->price              = $price;
        $this->duration_hours     = $duration_hours;
        $this->includes_equipment = $includes_equipment;
        $this->created_at         = date('Y-m-d H:i:s');
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
    public function getPrice(): float
    {
        return $this->price;
    }
    public function getDurationHours(): int
    {
        return $this->duration_hours;
    }
    public function getIncludesEquipment(): bool
    {
        return $this->includes_equipment;
    }
    public function getCreatedAt(): ?string
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
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }
    public function setDurationHours(int $hours): void
    {
        $this->duration_hours = $hours;
    }
    public function setIncludesEquipment(bool $includes): void
    {
        $this->includes_equipment = $includes;
    }

    public static function fromRow(array $row): Package
    {
        $package = new Package(
            $row['name'],
            (float) $row['price'],
            (int)   $row['duration_hours'],
            $row['description'],
            (bool)  $row['includes_equipment']
        );
        $package->id = $row['id'];
        $package->created_at = $row['created_at'];
        return $package;
    }
}
