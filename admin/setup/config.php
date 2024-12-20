<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'db_setup') {

    function generate_auth_key_setup($length = 64, $include_symbols = true) {
        if ($length < 32) {
            throw new Exception('AUTH_KEY length must be at least 32 characters for security.');
        }
    
        // Define character sets
        $sets = [
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',        // Uppercase letters
            'abcdefghijklmnopqrstuvwxyz',        // Lowercase letters
            '0123456789',                        // Numbers
        ];
    
        // Include symbols if specified
        if ($include_symbols) {
            $sets[] = '!@#$%^&*()-_=+[]{}|;:,.<>?/~`'; // Symbols
        }
    
        // Shuffle the sets to randomize their order
        shuffle($sets);
    
        $auth_key = '';
        $all_characters = implode('', $sets); // Combine all sets into one string
        $all_length = strlen($all_characters);
    
        // Ensure at least one character from each set is included
        foreach ($sets as $set) {
            $auth_key .= $set[random_int(0, strlen($set) - 1)];
        }
    
        // Fill the remaining length with random characters from the combined set
        for ($i = strlen($auth_key); $i < $length; $i++) {
            $auth_key .= $all_characters[random_int(0, $all_length - 1)];
        }
    
        // Shuffle the final key to ensure the placement of characters is random
        return str_shuffle($auth_key);
    }

    $db_host = (isset($_POST['db_host']) && !empty($_POST['db_host'])) ? $_POST['db_host'] : '';
    $db_username = (isset($_POST['db_username']) && !empty($_POST['db_username'])) ? $_POST['db_username'] : '';
    $db_password = (isset($_POST['db_password']) && !empty($_POST['db_password'])) ? $_POST['db_password'] : '';
    $db_name = (isset($_POST['db_name']) && !empty($_POST['db_name'])) ? $_POST['db_name'] : '';
    $table_prefix = (isset($_POST['table_prefix']) && !empty($_POST['table_prefix'])) ? $_POST['table_prefix'] : '';
    define('SITE_CONFIG_SETUP', true);
    define( 'DEBUG', false );
    define( 'DEBUG_DISPLAY', false );
    require_once CLASSES . 'class-db.php';
    $db = (new db($db_username, $db_password, $db_name, $db_host))->db_connect();
    if($db) {
        $config_sample_file = file_get_contents( ABSPATH . 'sync-config-sample.php' );
        if ($config_sample_file === false) {
            $error = 'Failed to read config-sample.php.';
        } else {
            // Replace placeholders with form values.
            $config_sample_file = str_replace(
                [
                    "define('DB_HOST', '');",
                    "define('DB_USER', '');",
                    "define('DB_PASS', '');",
                    "define('DB_NAME', '');",
                    "define('DB_PREFIX', '');",
                    "define('SECURE_AUTH_KEY', '');",
                    "define('LOGGED_IN_KEY', '');",
                    "define('NONCE_KEY', '');",
                    "define('AUTH_SALT', '');",
                    "define('SECURE_AUTH_SALT', '');",
                    "define('LOGGED_IN_SALT', '');",
                    "define('NONCE_SALT', '');",
                    "define('CACHE_KEY_SALT', '');",
                ],
                [
                    "define('DB_HOST', '$db_host');",
                    "define('DB_USER', '$db_username');",
                    "define('DB_PASS', '$db_password');",
                    "define('DB_NAME', '$db_name');",
                    "define('DB_PREFIX', '$table_prefix');",
                    "define('SECURE_AUTH_KEY', '" . generate_auth_key_setup() ."');",
                    "define('LOGGED_IN_KEY', '" . generate_auth_key_setup() ."');",
                    "define('NONCE_KEY', '" . generate_auth_key_setup() ."');",
                    "define('AUTH_SALT', '" . generate_auth_key_setup() ."');",
                    "define('SECURE_AUTH_SALT', '" . generate_auth_key_setup() ."');",
                    "define('LOGGED_IN_SALT', '" . generate_auth_key_setup() ."');",
                    "define('NONCE_SALT', '" . generate_auth_key_setup() ."');",
                    "define('CACHE_KEY_SALT', '" . generate_auth_key_setup() ."');",
                ],
                $config_sample_file
            );
            $new_file_path = ABSPATH . 'sync-config.php';
            if (!is_dir(dirname($new_file_path))) {
                mkdir(dirname($new_file_path), 0777, true);
            }

            // Write the updated content to config.php.
            if (file_put_contents($new_file_path, $config_sample_file) === false) {
                $error_message = 'Failed to create sync-config.php.';
            } else {

                // Redirect to /site-setup.
                header("Refresh:0");
                exit();
            }
        }
    } else {
        $error = 'Database connection failed. Please check your database credentials and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Setup</title>
    <link rel="stylesheet" href="/admin/assets/css/setup.css?minify">
</head>
<body>
    <div class="container">
        <form id="dbConfigForm" action="" method="post">
            <h1>Database Configuration</h1>
            
            <div class="form-group">
                <div class="form-label">Database Host</div>
                <div class="form-input">
                    <input type="text" id="dbHost" name="db_host" <?php echo isset($db_host) ? "value='$db_host' " : 'value="localhost" '; ?>required>
                    <small>Enter your database hostname (e.g., localhost or IP address)</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Database Username</div>
                <div class="form-input">
                    <input type="text" id="dbUsername" name="db_username" <?php echo isset($db_username) ? "value='$db_username' " : 'value="root" '; ?>required>
                    <small>Database user with access to create and modify tables</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Database Password</div>
                <div class="form-input">
                    <input type="text" id="dbPassword" name="db_password" <?php echo isset($db_password) ? "value='$db_password' " : 'value="root" '; ?>required>
                    <small>Password for the database user</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Database Name</div>
                <div class="form-input">
                    <input type="text" id="dbTable" name="db_name" <?php echo isset($db_name) ? "value='$db_name' " : 'value="local" '; ?>required>
                    <small>Name of the database to use for your application</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Table Prefix</div>
                <div class="form-input">
                    <input type="text" id="tablePrefix" name="table_prefix" <?php echo isset($table_prefix) ? "value='$table_prefix' " : 'value="sync_" '; ?>>
                    <small>Optional: Prefix for database tables (helps prevent conflicts)</small>
                </div>
            </div>

            <div class="form-group form-error">
                <?php echo isset($error) ? $error : ''; ?>
            </div>
            
            <input type="hidden" name="action" value="db_setup">
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Next: Site Setup</button>
            </div>
        </form>
    </div>
    <script src="/admin/assets/js/setup.js?minify"></script>
</body>
</html>
