<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/configwiz/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/configwiz/favicon.ico" type="image/x-icon">
    <title>ConfigWiz - Creo Configuration Manager</title>
    <?php include_once 'includes/analytics.php'; ?>
    <link rel="stylesheet" href="static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header>
        <a href="#" onclick="navigateHome(event)" class="header-title">
            <h1>ConfigWiz</h1>
            <p>Creo Configuration Management Tool</p>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="session-info">
            <i class="fa-solid fa-user"></i> Session: <?= substr($_SESSION['user_id'], 0, 8) ?>...
        </div>
        <?php endif; ?>
    </header>

    <!-- Persistent Search Bar -->
    <div class="persistent-search">
        <div class="search-wrapper">
            <div class="left-buttons">
                <!-- Start Over button removed as it's redundant with the ConfigWiz logo -->
            </div>
            <div class="search-container">
                <input type="text" id="global-search" placeholder="Search by name or description...">
                <div id="global-search-results"></div>
            </div>
            <div class="right-buttons">
                <?php if (!empty($user_changes)): ?>
                <a href="index.php?route=summary" class="search-bar-button primary">View Changes</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_category'])): ?>
            <div class="flash-messages">
                <div class="flash-message <?= $_SESSION['flash_category'] ?>">
                    <?= $_SESSION['flash_message'] ?>
                </div>
            </div>
            <?php
            // Clear flash messages after displaying them
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_category']);
            ?>
        <?php endif; ?>

        <?php if (empty($user_changes)): ?>
        <div class="card">
            <div class="collapsible-section">
                <button class="collapsible">Import Existing Configuration - Upload a config.pro file</button>
                <div class="collapsible-content">
                    <form action="index.php?route=upload" method="post" enctype="multipart/form-data" class="file-upload-form">
                        <div class="file-upload">
                            <label for="file-upload">Choose File</label>
                            <input type="file" id="file-upload" name="file" accept=".pro,.txt,.csv">
                            <span id="file-name" class="file-name"></span>
                        </div>
                        <button type="submit" class="btn">Upload</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($custom_categories)): ?>
        <div class="card">
            <h1>Custom Categories</h1>
            <div class="category-list">
                <?php foreach ($custom_categories as $cat): ?>
                <div class="category-item">
                    <a href="index.php?route=custom_category&category=<?= urlencode($cat) ?>">
                        <i class="fa-solid <?= get_category_icon($cat) ?> category-icon"></i>
                        <?= $cat ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h1>Configuration Categories</h1>
            <div class="version-info">
                <p>Creo <?= $version ?></p>
            </div>
            
            <div class="category-list">
                <?php 
                foreach ($categories as $cat): 
                $icon = get_category_icon($cat);
                ?>
                <div class="category-item">
                    <a href="index.php?route=configure&category=<?= urlencode($cat) ?>">
                        <i class="fa-solid <?= $icon ?> category-icon"></i>
                        <?= $cat ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="nav-links">
            <?php if (!empty($user_changes)): ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once 'includes/footer.php'; ?>

    <script src="static/js/script.js?v=<?= time() ?>"></script>
</body>
</html> 