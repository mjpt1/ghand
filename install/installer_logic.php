<?php
// --- Installer Logic Functions ---

/**
 * Writes the database configuration to the includes/config.php file.
 *
 * @param string $path The full path to the config file.
 * @param array $db_details An array with db_host, db_name, db_user, db_pass.
 * @return bool True on success, false on failure.
 */
function write_config_file(string $path, array $db_details): bool
{
    // The template for the config file.
    $config_template = <<<EOT
<?php
// --- Database Configuration ---
define('DB_HOST', '{$db_details['db_host']}');
define('DB_NAME', '{$db_details['db_name']}');
define('DB_USER', '{$db_details['db_user']}');
define('DB_PASS', '{$db_details['db_pass']}');

// --- Application Settings ---
define('BASE_URL', 'http://' . \$_SERVER['HTTP_HOST']);

// --- Security ---
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// !!! SECURITY WARNING: DO NOT USE THESE DEFAULT KEYS IN A PRODUCTION ENVIRONMENT !!!
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
define('JWT_SECRET', 'your-super-secret-key-please-change-me');
define('ENCRYPTION_KEY', 'your-32-byte-encryption-key-1234');
define('ENCRYPTION_IV', 'your-16-byte-iv-5678');

// --- PHP Settings ---
date_default_timezone_set('Asia/Tehran');
error_reporting(E_ALL);
ini_set('display_errors', 1);
mb_internal_encoding('UTF-8');
?>
EOT;

    // Write the contents to the file.
    if (file_put_contents($path, $config_template)) {
        return true;
    }
    return false;
}

/**
 * Imports the SQL schema from the DB.sql file.
 *
 * @param PDO \$pdo The PDO database connection object.
 * @param string \$sql_file_path The path to the DB.sql file.
 * @return bool|string True on success, error message string on failure.
 */
function import_sql_schema(PDO \$pdo, string \$sql_file_path)
{
    try {
        $sql = file_get_contents($sql_file_path);
        if ($sql === false) {
            return "امکان خواندن فایل SQL وجود ندارد.";
        }
        // Execute the entire SQL file.
        $pdo->exec($sql);
        return true;
    } catch (PDOException \$e) {
        return "خطا در ایمپورت پایگاه داده: " . $e->getMessage();
    }
}

/**
 * Creates the initial admin user.
 *
 * @param PDO \$pdo The PDO database connection object.
 * @param array \$admin_details Array with admin_email and admin_password.
 * @return bool|string True on success, error message string on failure.
 */
function create_admin_user(PDO \$pdo, array \$admin_details)
{
    try {
        $email = $admin_details['admin_email'];
        $password = $admin_details['admin_password'];

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the admin user
        $stmt = \$pdo->prepare(
            "INSERT INTO users (email, password, role, status) VALUES (?, ?, 'admin', 'active')"
        );
        $stmt->execute([$email, \$hashed_password]);

        // We can skip creating a profile for the admin in other tables.

        return true;
    } catch (PDOException \$e) {
        return "خطا در ساخت کاربر ادمین: " . $e->getMessage();
    }
}
?>
