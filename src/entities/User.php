<?php
class User
{
    protected ?int $id;
    protected string $full_name;
    protected string $username;
    protected string $email;
    protected string $password;
    protected string $profile_picture;
    protected string $created_at;

    public function __construct(
        string $full_name,
        string $username,
        string $email,
        string $password,
    ) {
        $this->full_name = $full_name;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->profile_picture = '/uploads/defaults/default_profile.png';
        $this->created_at = date('Y-m-d H:i:s');
    }

    // Getters 
    public function getFullName(): string
    {
        return $this->full_name;
    }
    public function getUsername(): string
    {
        return $this->username;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
    public function getProfilePicture(): string
    {
        return $this->profile_picture;
    }
    public function getCreatedAt(): string
    {
        return $this->created_at;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    // Setters 
    public function setFullName(string $full_name)
    {
        $this->full_name = $full_name;
    }
    public function setUsername(string $username)
    {
        $this->username = $username;
    }
    public function setEmail(string $email)
    {
        $this->email = $email;
    }
    public function setPassword(string $password)
    {
        $this->password = $password;
    }
    public function setProfilePicture(string $picture)
    {
        $this->profile_picture = $picture;
    }
}
