<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $photo_id = intval($_POST['photo_id']);
    $album_id = intval($_POST['album_id']);

    // First get the photo file name to delete the file from server
    $result = $conn->query("SELECT file_name FROM photos WHERE id = $photo_id");
    if ($result && $result->num_rows > 0) {
        $photo = $result->fetch_assoc();
        $filePath = "uploads/$album_id/" . $photo['file_name'];
        if (file_exists($filePath)) {
            unlink($filePath);  // delete the image file from server
        }
    }

    // Delete the photo record from the database
    $conn->query("DELETE FROM photos WHERE id = $photo_id");

    // Redirect back to the album page
    header("Location: view_album.php?id=$album_id");
    exit;
} else {
    echo "Invalid request.";
}
