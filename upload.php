<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo']) && isset($_POST['album_id'])) {
    // Handle file upload
    $album_id = intval($_POST['album_id']);
    $fileName = basename($_FILES["photo"]["name"]);

    $targetDir = "uploads/" . $album_id . "/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $targetFilePath = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
        $stmt = $conn->prepare("INSERT INTO photos (album_id, file_name) VALUES (?, ?)");
        $stmt->bind_param("is", $album_id, $fileName);
        $stmt->execute();
        $stmt->close();
        header("Location: view_album.php?id=" . $album_id);
        exit;
    } else {
        echo "Error uploading file.";
    }
} else {
    // Show upload form
    $album_id = isset($_GET['album_id']) ? intval($_GET['album_id']) : 0;

    if ($album_id <= 0) {
        die("Invalid album.");
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Upload Photo</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <h2>Upload a Photo to Album #<?php echo $album_id; ?></h2>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="album_id" value="<?php echo $album_id; ?>">
            <input type="file" name="photo" required>
            <button type="submit">Upload</button>
        </form>
    </body>
    </html>
    <?php
}
?>
<?php
// Your existing PHP upload handling code here...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Upload Photo</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="container">
        <a href="albums.php" class="btn" style="margin-top: 20px;">← Back to Albums</a>

        

