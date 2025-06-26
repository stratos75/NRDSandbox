<?php
session_start();

// Set default values
$defaults = [
  'player_hp' => 100,
  'player_atk' => 30,
  'player_def' => 15,
  'enemy_hp'  => 100,
  'enemy_atk' => 25,
  'enemy_def' => 10
];

// Save submitted form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($defaults as $key => $default) {
        $_SESSION[$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $default;
    }
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Configure Mechs</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 40px;
    }

    form {
      max-width: 400px;
      margin: 0 auto;
    }

    label {
      display: block;
      margin-top: 15px;
    }

    input[type="number"] {
      width: 100%;
      padding: 6px;
      margin-top: 4px;
    }

    button {
      margin-top: 20px;
      padding: 10px 20px;
    }
  </style>
</head>
<body>

<h2>Configure Mech Stats</h2>

<form method="post">
  <?php foreach ($defaults as $key => $default): ?>
    <label>
      <?= strtoupper($key) ?>
      <input type="number" name="<?= $key ?>" value="<?= $_SESSION[$key] ?? $default ?>">
    </label>
  <?php endforeach; ?>

  <button type="submit">Save and Launch</button>
</form>

</body>
</html>
