<?php

require_once 'User.php';

class Admin extends User
{
    public function __construct(
        string $full_name,
        string $username,
        string $email,
        string $password,
    ) {
        parent::__construct($full_name, $username, $email, $password);
    }

    public static function fromRow(array $row): Admin
    {
        $admin = new Admin(
            $row['full_name'],
            $row['username'],
            $row['email'],
            $row['password']
        );
        $admin->id = $row['id'];
        $admin->profile_picture = $row['profile_picture'];
        $admin->created_at = $row['created_at'];
        return $admin;
    }
}
