<?php
defined('NEDA') or exit;

function neda_head(string $title): void
{
    $csrf = neda_csrf_token();
    ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo neda_h($title); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css?v=call-fs1">
  <meta name="csrf-token" content="<?php echo neda_h($csrf); ?>">
</head>
<body>
    <?php
}

function neda_foot(): void
{
    echo "</body>\n</html>\n";
}
