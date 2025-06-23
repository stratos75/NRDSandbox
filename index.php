<?php require 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>NRD Sandbox</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f8f8f8;
    }

    .battlefield {
      display: flex;
      flex-direction: column;
      height: 100vh;
      justify-content: space-between;
    }

    .row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex: 1;
      padding: 40px;
      position: relative;
    }

    .divider {
      height: 4px;
      background-color: #222;
      width: 100%;
    }

    .card {
      width: 100px;
      height: 140px;
      background-color: #777;
      border-radius: 8px;
      box-shadow: 2px 2px 6px rgba(0,0,0,0.3);
      position: relative;
    }

    .circle {
      width: 30px;
      height: 30px;
      background-color: #ccc;
      border-radius: 50%;
      position: absolute;
      top: 10px;
      left: 10px;
    }

    .stack {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .stack .card {
      margin-top: -20px;
    }

    .label {
      text-align: center;
      font-size: 14px;
      margin-top: 5px;
    }
  </style>
</head>
<body>

  <div class="battlefield">
    
    <!-- Enemy Side -->
    <div class="row">
      <div></div>

      <div>
        <div class="card">
          <div class="circle"></div>
        </div>
        <div class="label">Enemy Mech</div>
      </div>

      <div class="stack">
        <div class="card"></div>
        <div class="card"></div>
        <div class="card"></div>
        <div class="label">Enemy Deck</div>
      </div>
    </div>

    <!-- Divider Line -->
    <div class="divider"></div>

    <!-- Player Side -->
    <div class="row">
      <div class="stack">
        <div class="card"></div>
        <div class="card"></div>
        <div class="card"></div>
        <div class="label">Your Deck</div>
      </div>

      <div>
        <div class="card">
          <div class="circle"></div>
        </div>
        <div class="label">Your Mech</div>
      </div>

      <div></div>
    </div>

  </div>

</body>
</html>
