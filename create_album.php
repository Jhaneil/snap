<?php include 'db.php'; ?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $album_name = $_POST['album_name'];
    $conn->query("INSERT INTO albums (album_name) VALUES ('$album_name')");
    header("Location: albums.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Mobile responsive -->
    <title>Create Album</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Create New Album</h1>
        <form method="POST">
            <input type="text" name="album_name" placeholder="Album Name" required>
            <button type="submit" class="btn">Create</button>
        </form>
    </div>
</body>
</html>
