<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Fetch user data
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            header("Location: albums.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Mobile responsiveness meta tag -->
  <title>Login - SnapBox</title>
  <link href="https://fonts.googleapis.com/css2?family=Poiret+One&family=Quicksand:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Quicksand', sans-serif;
      height: 100vh;
      background: linear-gradient(135deg, #6ee7b7, #3b82f6);
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      text-align: center;
      position: relative;
    }

    .logo-container img {
      width: 200px;  /* Logo size can be adjusted */
      margin-bottom: 10px;
      filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3));
    }

    h1 {
      font-size: 3em;
      font-family: 'Poiret One', cursive;
      margin: 0;
    }

    p.tagline {
      font-size: 1.2em;
      margin-bottom: 30px;
    }

    input[type="text"], input[type="password"] {
      width: 80%;
      padding: 10px;
      margin: 10px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 1em;
    }

    button.submit-btn {
      width: 80%;
      padding: 12px 28px;
      background-color: #3b82f6;
      color: white;
      border: none;
      border-radius: 30px;
      font-weight: bold;
      cursor: pointer;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      transition: 0.3s;
    }

    button.submit-btn:hover {
      background-color: #2563eb;
    }

    footer {
      position: absolute;
      bottom: 10px;
      font-size: 0.8em;
      opacity: 0.8;
    }

    .error {
      color: red;
      font-size: 1.1em;
    }
  </style>
</head>
<body>

  <div class="logo-container">
    <img src="logo.png" alt="SnapBox Logo">
  </div>
  <h1>SnapBox</h1>
  <p class="tagline">Capture. Keep. Cherish.</p>
  
  <!-- Display error message if any -->
  <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
  
  <!-- Login Form -->
  <form method="POST" action="" style="text-align:center;">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit" class="submit-btn">Sign In</button>
  </form>

  <footer>© Jhaneil Delos Reyes 2025.</footer>

</body>
</html>
