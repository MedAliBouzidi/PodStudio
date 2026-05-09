<?php

require_once "User.php";

class Client extends User
{
    private ?string $phone;

    public function __construct(
        string $full_name,
        string $username,
        string $password,
        string $email,
        string $phone
    ) {
        parent::__construct($full_name, $username, $email, $password);
        $this->phone = $phone;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone)
    {
        $this->phone = $phone;
    }

    public static function fromRow(array $row): Client
    {
        $client = new Client(
            $row['full_name'],
            $row['username'],
            $row['password'],
            $row['email'],
            $row['phone']
        );
        $client->id = $row['id'];
        $client->profile_picture = $row['profile_picture'];
        $client->created_at = $row['created_at'];
        return $client;
    }
}