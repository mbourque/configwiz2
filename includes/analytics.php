<?php
// Load configuration
require_once 'config.php';

// Only output analytics code if ID is configured
if (!empty($config['google_analytics_id'])):
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= $config['google_analytics_id'] ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '<?= $config['google_analytics_id'] ?>');
</script>
<?php endif; ?> 