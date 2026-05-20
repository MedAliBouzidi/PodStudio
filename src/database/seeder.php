<?php
// Access via browser: http://podstudio.test/database/seeder.php
// Delete this file after running it once!

// ── Safety lock: only run once ───────────────────────────────
use Random\RandomException;

$lock_file = __DIR__ . '/seeder.lock';
if (file_exists($lock_file)) {
    die("<h2>⛔ Seeder already ran.</h2><p>Delete <code>database/seeder.lock</code> to run again.</p>");
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../entities/Status.php';
require_once __DIR__ . '/../entities/User.php';
require_once __DIR__ . '/../entities/Admin.php';
require_once __DIR__ . '/../entities/Client.php';
require_once __DIR__ . '/../entities/Studio.php';
require_once __DIR__ . '/../entities/Equipment.php';
require_once __DIR__ . '/../entities/Package.php';
require_once __DIR__ . '/../entities/Booking.php';
require_once __DIR__ . '/../services/AdminService.php';
require_once __DIR__ . '/../services/ClientService.php';
require_once __DIR__ . '/../services/StudioService.php';
require_once __DIR__ . '/../services/EquipmentService.php';
require_once __DIR__ . '/../services/PackageService.php';
require_once __DIR__ . '/../services/BookingService.php';

$pdo = Database::getConnection();
$log = [];

function step(string $msg): void
{
    global $log;
    $log[] = "<li>$msg</li>";
}
// Drop old Database
$pdo->query("DROP TABLE IF EXISTS `admins`");
$pdo->query("DROP TABLE IF EXISTS `bookings`");
$pdo->query("DROP TABLE IF EXISTS `clients`");
$pdo->query("DROP TABLE IF EXISTS `equipments`");
$pdo->query("DROP TABLE IF EXISTS `packages`");
$pdo->query("DROP TABLE IF EXISTS `studios`");
echo "Old Database dropped";

// ── Create Tables ────────────────────────────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS admins (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        full_name       VARCHAR(100) NOT NULL,
        username        VARCHAR(50)  NOT NULL,
        email           VARCHAR(100) NOT NULL UNIQUE,
        password        VARCHAR(255) NOT NULL,
        profile_picture VARCHAR(255) DEFAULT '/images/default.png',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");
step("✅ Table <b>admins</b> ready");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS clients (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        full_name       VARCHAR(100) NOT NULL,
        username        VARCHAR(50)  NOT NULL,
        email           VARCHAR(100) NOT NULL UNIQUE,
        password        VARCHAR(255) NOT NULL,
        phone           VARCHAR(20)  DEFAULT NULL,
        profile_picture VARCHAR(255) DEFAULT '/images/default.png',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");
step("✅ Table <b>clients</b> ready");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS studios (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        name           VARCHAR(100)  NOT NULL,
        description    TEXT          DEFAULT NULL,
        location       VARCHAR(150)  DEFAULT NULL,
        capacity       INT           NOT NULL,
        price_per_hour DECIMAL(10,2) NOT NULL,
        cover_image    VARCHAR(255)  DEFAULT 'studio_default.png',
        status         ENUM('available','in_use','maintenance') DEFAULT 'available',
        created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");
step("✅ Table <b>studios</b> ready");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS equipments (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        studio_id   INT          NOT NULL,
        name        VARCHAR(100) NOT NULL,
        brand       VARCHAR(100) DEFAULT NULL,
        description TEXT         DEFAULT NULL,
        image       VARCHAR(255) DEFAULT 'equipment_default.png',
        quantity    INT          DEFAULT 1,
        status      ENUM('available','in_use','maintenance') DEFAULT 'available',
        FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE CASCADE
    );
");
step("✅ Table <b>equipments</b> ready");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS packages (
        id                 INT AUTO_INCREMENT PRIMARY KEY,
        name               VARCHAR(100)  NOT NULL,
        description        TEXT          DEFAULT NULL,
        price              DECIMAL(10,2) NOT NULL,
        duration_hours     INT           NOT NULL,
        includes_equipment TINYINT(1)    DEFAULT 1,
        created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");
step("✅ Table <b>packages</b> ready");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS bookings (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT           NOT NULL,
        studio_id   INT           NOT NULL,
        package_id  INT           DEFAULT NULL,
        date        DATE          NOT NULL,
        start_time  TIME          NOT NULL,
        end_time    TIME          NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        status      ENUM('pending','confirmed','canceled') DEFAULT 'pending',
        notes       TEXT          DEFAULT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id)    REFERENCES clients(id)  ON DELETE CASCADE,
        FOREIGN KEY (studio_id)  REFERENCES studios(id)  ON DELETE CASCADE,
        FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
    );
");
step("✅ Table <b>bookings</b> ready");

// ── Admins ───────────────────────────────────────────────────
$adminService = new AdminService();
$admins = [
        new Admin('Super Admin', 'admin', 'admin@podstudio.com', 'admin'),
        new Admin('Mohamed Cherif', 'mcherif', 'mcherif@podstudio.com', 'admin456'),
];
$adminIds = [];
foreach ($admins as $a) {
    $adminIds[] = $adminService->save($a);
    step("✅ Admin: <b>{$a->getUsername()}</b>");
}

// ── Clients ──────────────────────────────────────────────────
$clientService = new ClientService();
$clientImages = [
        '0ba209aa9b55939ff374cef950856fbf.jpg',
        '1159b41c63d63e31c72f47750e5f07ae.jpg',
        '13600987b0331ef67352e955c6373f21.jpg',
        '169fba1da31271eb97f9827443602a90.jpg',
        '16a6555beb4f1fbb83f0a45849ddb8a3.jpg'
];
$clientIds = [];
try {
    $clients = [
            new Client('Ali Ben Salah', 'ali_podcast', 'pass1234', 'ali@gmail.com', $clientImages[random_int(0, count($clientImages) - 1)], '0661234567'),
            new Client('Sara Meziani', 'sara_mic', 'pass1234', 'sara@gmail.com', $clientImages[random_int(0, count($clientImages) - 1)], '0772345678'),
            new Client('Karim Boukhari', 'karim_sound', 'pass1234', 'karim@gmail.com', $clientImages[random_int(0, count($clientImages) - 1)], '0553456789'),
            new Client('Nora Benali', 'nora_waves', 'pass1234', 'nora@gmail.com', $clientImages[random_int(0, count($clientImages) - 1)], '0664567890'),
            new Client('Yacine Larbi', 'yacine_pod', 'pass1234', 'yacine@gmail.com', $clientImages[random_int(0, count($clientImages) - 1)], '0771234000'),
    ];
    foreach ($clients as $c) {
        $clientIds[] = $clientService->save($c);
        step("✅ Client: <b>{$c->getUsername()}</b>");
    }
} catch (RandomException $e) {
    echo $e->getMessage();
}

// ── Studios ──────────────────────────────────────────────────
$studioImages = [
        '0835084616307b421c681bbac8fb2d80.jpg',
        '0984034823e014b410895060290e66a4.jpg',
        '19e5eef8ee474502fd6b5a1bb6f0aaf2.jpg',
        '1a210cf71ca8c0c91f7a0aa2c08803c1.jpg',
        '1f7d3a9e8f6a4e93b832c82a2ccd7094.jpg'
];
$studioService = new StudioService();
$studioIds = [];
try {
    $studios = [
            new Studio('Studio Alpha', 4, 250.00, 'Professional podcast studio with soundproofing and 4K video setup.', 'Ariana - Tunisia', $studioImages[random_int(0, count($studioImages) - 1)], Status::Available),
            new Studio('Studio Beta', 2, 150.00, 'Compact solo studio perfect for voice-over and interview recordings.', 'Sousse - Tunisia', $studioImages[random_int(0, count($studioImages) - 1)], Status::Available),
            new Studio('Studio Gamma', 6, 350.00, 'Large studio for panel shows, live recordings and music podcasts.', 'Tunis - Cité Universitaire', $studioImages[random_int(0, count($studioImages) - 1)], Status::Available),
            new Studio('Studio Delta', 2, 100.00, 'Budget-friendly studio with essential equipment for beginners.', 'Sidi Bouzid - Tunisia', $studioImages[random_int(0, count($studioImages) - 1)], Status::Maintenance),
            new Studio('Studio Echo', 5, 450.00, 'High-end studio with Dolby Atmos mixing and green screen background.', 'Sousse - Tunisia', $studioImages[random_int(0, count($studioImages) - 1)], Status::Available),
    ];
    foreach ($studios as $s) {
        $studioIds[] = $studioService->save($s);
        step("✅ Studio: <b>{$s->getName()}</b> — {$s->getStatus()->value}");
    }
} catch (RandomException $e) {
    echo $e->getMessage();
}


// ── Equipments ───────────────────────────────────────────────
$equipmentService = new EquipmentService();
$equipmentImages = [
        'audio_interface_08b0ed62356899c7.jpg',
        'audio_interface_12f21eba38f36b6b.jpg',
        'audio_interface_1424c8e008fc9d4f.jpg',
        'audio_interface_1ba86658f561bb82.jpg',
        'audio_interface_2a8471791be1239a.jpg',
        'audio_interface_350a759b64a888d4.jpg',
        'audio_interface_3b1e7da309ab5c29.jpg',
        'audio_interface_536ca137cc831967.jpg',
        'audio_interface_6821dc1b1d618715.jpg',
        'audio_interface_91e3ac96169c305d.jpg',
        'audio_interface_a12918a013eb2a94.jpg',
        'audio_interface_ac849a59e06cfcda.jpg',
        'audio_interface_af08e4026644395b.jpg',
        'audio_interface_b1b4d1025cc75817.jpg',
        'audio_interface_b6d8705d1425ff55.jpg'
];
try {
    $equipmentList = [
            new Equipment($studioIds[0], 'Condenser Microphone', 'Rode', 'Rode NT1 studio microphone', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 4, Status::Available),
            new Equipment($studioIds[0], 'Audio Interface', 'Focusrite', 'Scarlett 4i4 4-channel interface', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 1, Status::Available),
            new Equipment($studioIds[0], 'Studio Monitor', 'Yamaha', 'HS8 powered studio monitors', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 2, Status::Available),
            new Equipment($studioIds[0], '4K Camera', 'Sony', 'Sony ZV-E10 for video podcast recording', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 2, Status::Available),
            new Equipment($studioIds[1], 'Dynamic Microphone', 'Shure', 'Shure SM7B broadcast microphone', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 2, Status::Available),
            new Equipment($studioIds[1], 'Audio Interface', 'Focusrite', 'Scarlett Solo interface', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 1, Status::Available),
            new Equipment($studioIds[1], 'Pop Filter', 'Generic', 'Double-layer nylon pop filter', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 2, Status::Available),
            new Equipment($studioIds[2], 'Condenser Microphone', 'AKG', 'AKG C414 multi-pattern microphone', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 6, Status::Available),
            new Equipment($studioIds[2], 'Mixing Console', 'Behringer', 'Behringer X32 digital mixer', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 1, Status::Available),
            new Equipment($studioIds[2], 'Podcast Boom Arm', 'Rode', 'Rode PSA1 professional boom arm', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 6, Status::Available),
            new Equipment($studioIds[3], 'USB Microphone', 'Blue', 'Blue Yeti USB microphone', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 2, Status::Available),
            new Equipment($studioIds[3], 'Headphones', 'Sony', 'Sony MDR-7506 studio headphones', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 2, Status::Maintenance),
            new Equipment($studioIds[4], 'Ribbon Microphone', 'Royer', 'Royer R-121 ribbon microphone', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 4, Status::Available),
            new Equipment($studioIds[4], 'Atmos Processor', 'Dolby', 'Dolby Atmos mixing processor unit', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 1, Status::Available),
            new Equipment($studioIds[4], 'Green Screen', 'Elgato', 'Elgato collapsible green screen 1.8m', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 1, Status::Available),
            new Equipment($studioIds[4], '4K Camera', 'Canon', 'Canon EOS R50 with podcast mount', $equipmentImages[random_int(0, count($equipmentImages) - 1)], 3, Status::Available),
    ];
    foreach ($equipmentList as $eq) {
        $equipmentService->save($eq);
        step("✅ Equipment: <b>{$eq->getName()}</b> ({$eq->getBrand()}) → Studio #{$eq->getStudioId()}");
    }
} catch (RandomException $e) {
    echo $e->getMessage();
}

// ── Packages ─────────────────────────────────────────────────
$packageService = new PackageService();
$packages = [
        new Package('Starter Pack', 200.00, 2, 'Basic 2-hour session, no extras included.', false),
        new Package('Creator Pack', 500.00, 4, 'Half-day session with full equipment access.', true),
        new Package('Pro Pack', 1000.00, 8, 'Full-day recording with mixing and editing support.', true),
        new Package('Interview Pack', 400.00, 3, '3-hour session setup for 2-person interview format.', true),
        new Package('Live Stream Pack', 700.00, 4, '4-hour session with streaming setup and green screen.', true),
];
$packageIds = [];
foreach ($packages as $pkg) {
    $packageIds[] = $packageService->save($pkg);
    step("✅ Package: <b>{$pkg->getName()}</b> — {$pkg->getPrice()} TND");
}

// ── Bookings ─────────────────────────────────────────────────
$bookingService = new BookingService();
$bookings = [
        new Booking($clientIds[0], $studioIds[0], '2026-05-05', '09:00:00', '13:00:00', 5000.00, $packageIds[1], Status::Confirmed->value, 'First episode recording'),
        new Booking($clientIds[1], $studioIds[1], '2026-05-06', '14:00:00', '16:00:00', 2000.00, $packageIds[0], Status::Pending->value, 'Solo voice-over session'),
        new Booking($clientIds[2], $studioIds[2], '2026-05-07', '08:00:00', '16:00:00', 10000.00, $packageIds[2], Status::Confirmed->value, 'Full day panel podcast'),
        new Booking($clientIds[3], $studioIds[4], '2026-05-08', '10:00:00', '14:00:00', 7000.00, $packageIds[4], Status::Confirmed->value, 'Live stream setup needed'),
        new Booking($clientIds[0], $studioIds[1], '2026-05-10', '13:00:00', '16:00:00', 4000.00, $packageIds[3], Status::Canceled->value, 'Cancelled due to travel'),
        new Booking($clientIds[1], $studioIds[0], '2026-05-12', '09:00:00', '13:00:00', 5000.00, $packageIds[1], Status::Pending->value, 'Interview with guest speaker'),
        new Booking($clientIds[2], $studioIds[4], '2026-05-15', '08:00:00', '16:00:00', 10000.00, $packageIds[2], Status::Confirmed->value, 'Season finale recording'),
        new Booking($clientIds[3], $studioIds[2], '2026-05-18', '11:00:00', '14:00:00', 4000.00, $packageIds[3], Status::Pending->value, 'Roundtable discussion'),
        new Booking($clientIds[4], $studioIds[0], '2026-05-20', '09:00:00', '13:00:00', 5000.00, $packageIds[1], Status::Pending->value, 'First solo podcast episode'),
];
foreach ($bookings as $b) {
    $id = $bookingService->save($b);
    step("✅ Booking <b>#$id</b> — Studio #{$b->getStudioId()} | {$b->getDate()} | <b>{$b->getStatus()}</b>");
}

// ── Create lock file ─────────────────────────────────────────
file_put_contents($lock_file, date('Y-m-d H:i:s'));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PodStudio — Seeder</title>
    <style>
        body {
            font-family: monospace;
            background: #0f0f0f;
            color: #e0e0e0;
            padding: 2rem;
        }

        h1 {
            color: #f97316;
        }

        ul {
            line-height: 2;
        }

        .done {
            margin-top: 2rem;
            background: #14532d;
            color: #86efac;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-size: 1.1rem;
        }

        .warn {
            margin-top: 1rem;
            background: #7c2d12;
            color: #fdba74;
            padding: .75rem 1.5rem;
            border-radius: 8px;
        }
    </style>
</head>

<body>
<h1>🎙️ PodStudio — Database Seeder</h1>
<ul><?= implode("\n", $log) ?></ul>
<div class="done">✅ Seeding complete! Your database is ready.</div>
<div class="warn">⚠️ Delete or restrict access to <code>seeder.php</code> now that it has run.</div>
</body>

</html>