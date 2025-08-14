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

<!-- Simple HTML form if you want to test standalone -->
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST" action="" style="text-align:center;">
  <!-- Logo added here -->
  <img src="logo.png" alt="Logo" style="width:100px; margin-bottom:15px;" />
  
  <input type="text" name="username" placeholder="Username" required /><br><br>
  <input type="password" name="password" placeholder="Password" required /><br><br>
  <button type="submit">Sign In</button>
</form>
