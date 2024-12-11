<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Setup</title>
    <link rel="stylesheet" href="/admin/assets/css/setup.css">
</head>
<body>
    <div class="container">
        <form id="siteConfigForm" action="" method="post">
            <h1>Site Configuration</h1>
            
            <div class="form-group">
                <div class="form-label">Site Name</div>
                <div class="form-input">
                    <input type="text" id="siteName" name="site_name" required>
                    <small>Enter the name of your website</small>
                </div>
            </div>
            
            <h2>User Settings</h2>
            
            <div class="form-group">
                <div class="form-label">Full Name</div>
                <div class="form-input">
                    <input type="text" id="userName" name="user_name" required>
                    <small>Your full name</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Login Username</div>
                <div class="form-input">
                    <input type="text" id="loginUsername" name="login_username" required>
                    <small>Username can only contain A-Z, a-z, 0-9, and underscore</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Password</div>
                <div class="form-input">
                    <input type="password" id="userPassword" name="user_password" required>
                    <small>Minimum 10 characters</small>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-label">Email Address</div>
                <div class="form-input">
                    <input type="email" id="userEmail" name="user_email" required>
                    <small>Valid email format required</small>
                </div>
            </div>
            
            <div class="form-group search-visibility">
                <div class="form-label">Search Engine Visibility</div>
                <div class="form-input radio-group">
                    <input type="radio" id="searchVisible" name="search_visibility" value="visible" checked>
                    <label for="searchVisible">Visible to Search Engines</label>
                    
                    <input type="radio" id="searchHidden" name="search_visibility" value="hidden">
                    <label for="searchHidden">Hidden from Search Engines</label>
                </div>
            </div>
            
            <input type="hidden" name="action" value="site_setup">
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Complete Setup</button>
            </div>
        </form>
    </div>
    <script src="/admin/assets/js/setup.js"></script>
</body>
</html>