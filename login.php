<!-- login.php -->
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Urenregistratie</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

  <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
    <h4 class="mb-3 text-center">Urenregistratie Inloggen</h4>
    <form action="dashboard.php" method="POST">
      <div class="mb-3">
        <label for="email" class="form-label">E-mailadres</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="naam@voorbeeld.nl" required>
      </div>
      <div class="mb-3">
        <label for="pin" class="form-label">Pincode</label>
        <input type="password" class="form-control" id="pin" name="pin" placeholder="****" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Inloggen</button>
    </form>
  </div>

</body>
</html>
