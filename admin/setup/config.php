<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Setup</title>
    <link rel="stylesheet" href="/admin/assets/css/setup.css">
</head>
<body>
    <div class="container">
        <form id="dbConfigForm" action="" method="post">
            <h1>Database Configuration</h1>
            
            <div class="form-group">
                <div class="form-label">Database Host</div>
                <div class="form-input">
                    <input type="text" id="dbHost" name="db_host" required>
                    <small>Enter your database hostname (e.g., localhost or IP address)</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Database Username</div>
                <div class="form-input">
                    <input type="text" id="dbUsername" name="db_username" required>
                    <small>Database user with access to create and modify tables</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Database Password</div>
                <div class="form-input">
                    <input type="password" id="dbPassword" name="db_password" required>
                    <small>Password for the database user</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Database Name</div>
                <div class="form-input">
                    <input type="text" id="dbTable" name="db_table" required>
                    <small>Name of the database to use for your application</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Table Prefix</div>
                <div class="form-input">
                    <input type="text" id="tablePrefix" name="table_prefix">
                    <small>Optional: Prefix for database tables (helps prevent conflicts)</small>
                </div>
            </div>
            
            <input type="hidden" name="action" value="db_setup">
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Next: Site Setup</button>
            </div>
        </form>
    </div>
    <script src="/admin/assets/js/setup.js"></script>
</body>
</html>
