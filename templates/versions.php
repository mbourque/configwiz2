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
        <a href="index.php" class="header-title">
            <h1>ConfigWiz</h1>
            <p>Creo Configuration Management Tool</p>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="session-info">
            <i class="fa-solid fa-user"></i> Session: <?= substr($_SESSION['user_id'], 0, 8) ?>...
        </div>
        <?php endif; ?>
    </header>

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

        <div class="banner">
            <p>Select a Creo version to configure your CAD/CAM settings. ConfigWiz helps you manage and export your Creo configuration parameters with ease.</p>
        </div>

        <?php
        // Get available versions
        $versions = get_available_versions();
        
        // If versions are found, display them
        if (!empty($versions)): 
        ?>
            <div class="versions-container">
                <?php foreach ($versions as $version): ?>
                    <div class="version-card">
                        <div class="version-header">
                            <h2>Creo <?= $version['version'] ?></h2>
                        </div>
                        <div class="version-body">
                            <div>
                                <div class="version-icon">
                                    <i class="fa-solid fa-cog"></i>
                                </div>
                                <div class="version-description">
                                    Configure settings for Creo Parametric version <?= $version['version'] ?>.
                                </div>
                            </div>
                            <a href="index.php?route=version&version=<?= $version['version'] ?>" class="select-version-btn">
                                Configure
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>No Versions Available</h2>
                <p>No configuration files were found. Please add Creo configuration CSV files to the configs directory.</p>
            </div>
        <?php endif; ?>
    </div>

    <footer class="site-footer">
        <div class="footer-content">
            <p>Created by: <a href="mailto:michael@ptcuserboston.org">Michael P. Bourque</a> | <a href="https://github.com/mbourque/configwiz2" target="_blank"><i class="fa-brands fa-github"></i> GitHub</a></p>
        </div>
    </footer>

    <script src="static/js/script.js?v=<?= time() ?>"></script>
</body>
</html> 