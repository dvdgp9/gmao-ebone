<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>1. PHP funciona</h2>";
echo "PHP version: " . PHP_VERSION . "<br>";

echo "<h2>2. Extensiones</h2>";
echo "PDO: " . (extension_loaded('pdo') ? 'OK' : 'FALTA') . "<br>";
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'OK' : 'FALTA') . "<br>";
echo "mbstring: " . (extension_loaded('mbstring') ? 'OK' : 'FALTA') . "<br>";

echo "<h2>3. Autoload</h2>";
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
echo "Path: {$autoload}<br>";
echo "Exists: " . (file_exists($autoload) ? 'SI' : 'NO') . "<br>";

if (file_exists($autoload)) {
    try {
        require_once $autoload;
        echo "Autoload: OK<br>";
    } catch (Throwable $e) {
        echo "Autoload ERROR: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>4. .env</h2>";
$envFile = dirname(__DIR__) . '/.env';
echo "Path: {$envFile}<br>";
echo "Exists: " . (file_exists($envFile) ? 'SI' : 'NO') . "<br>";

if (file_exists($envFile)) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();
        echo "Dotenv: OK<br>";
        echo "APP_URL: " . ($_ENV['APP_URL'] ?? 'no definido') . "<br>";
        echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'no definido') . "<br>";
        echo "DB_DATABASE: " . ($_ENV['DB_DATABASE'] ?? 'no definido') . "<br>";
    } catch (Throwable $e) {
        echo "Dotenv ERROR: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>5. Connexió BD</h2>";
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_PORT'] ?? '3306',
        $_ENV['DB_DATABASE'] ?? '',
        $_ENV['DB_CHARSET'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'] ?? '', $_ENV['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Connexió: OK<br>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Taules: " . implode(', ', $tables) . "<br>";
} catch (Throwable $e) {
    echo "BD ERROR: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Paths</h2>";
echo "__DIR__: " . __DIR__ . "<br>";
echo "dirname(__DIR__): " . dirname(__DIR__) . "<br>";
echo "document_root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "<br>";
