<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['album_id'])) {
        $album_id = intval($_POST['album_id']);

        // Delete photos in the album first
        $conn->query("DELETE FROM photos WHERE album_id = $album_id");

        // Delete the album
        $conn->query("DELETE FROM albums WHERE id = $album_id");

        // Optional: Delete album folder and files from uploads (if you store files in folders)
        $uploadDir = __DIR__ . "/uploads/$album_id";
        if (is_dir($uploadDir)) {
            $files = glob($uploadDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) unlink($file);
            }
            rmdir($uploadDir);
        }

        // Redirect back to albums page
        header("Location: albums.php");
        exit;
    }
}

http_response_code(400);
echo "Invalid request.";

