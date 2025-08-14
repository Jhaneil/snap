<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if show favorites mode is on
$show_favorites = (isset($_GET['show']) && $_GET['show'] === 'favorites');

// Handle adding a new album
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_album_name'])) {
    $new_album_name = trim($_POST['new_album_name']);
    if ($new_album_name !== '') {
        $insert = $conn->prepare("INSERT INTO albums (user_id, album_name, favorite) VALUES (?, ?, 0)");
        $insert->bind_param("is", $user_id, $new_album_name);
        $insert->execute();
        $insert->close();
    }
    header("Location: albums.php" . ($show_favorites ? "?show=favorites" : ""));
    exit();
}

// Handle toggling favorite
if (isset($_GET['toggle_fav']) && is_numeric($_GET['toggle_fav'])) {
    $album_id = intval($_GET['toggle_fav']);
    // Get current favorite status
    $stmt = $conn->prepare("SELECT favorite FROM albums WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $album_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($current_fav);
    if ($stmt->fetch()) {
        $stmt->close();
        $new_fav = $current_fav ? 0 : 1;
        $update = $conn->prepare("UPDATE albums SET favorite = ? WHERE id = ? AND user_id = ?");
        $update->bind_param("iii", $new_fav, $album_id, $user_id);
        $update->execute();
        $update->close();
    } else {
        $stmt->close();
    }
    // Redirect keeping show=favorites param if active
    header("Location: albums.php" . ($show_favorites ? "?show=favorites" : ""));
    exit();
}

// Fetch albums with optional favorite filter
if ($show_favorites) {
    $stmt = $conn->prepare("SELECT id, album_name, favorite FROM albums WHERE user_id = ? AND favorite = 1");
} else {
    $stmt = $conn->prepare("SELECT id, album_name, favorite FROM albums WHERE user_id = ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Albums</title>
  <link href="https://fonts.googleapis.com/css2?family=Poiret+One&family=Quicksand:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      padding: 0;
      font-family: 'Quicksand', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #6ee7b7, #3b82f6);
      color: white;
      display: flex;
      flex-direction: column;
      height: 100vh;
    }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 40px;
    }
    header .logo {
      display: flex;
      align-items: center;
    }
    header .logo img {
      height: 100px;
      object-fit: contain;
    }
    header .welcome-text {
      margin-left: 12px;
      font-weight: 600;
      font-size: 1.2em;
      color: white;
    }
    .logout-btn {
      background: white;
      color: #3b82f6;
      border: none;
      border-radius: 30px;
      padding: 10px 22px;
      font-weight: bold;
      cursor: pointer;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      transition: 0.3s;
      font-family: 'Quicksand', sans-serif;
    }
    .logout-btn:hover {
      background: #3b82f6;
      color: white;
    }
    main {
      flex-grow: 1;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding: 40px 20px;
    }
    .albums-container {
      max-width: 480px;
      background: rgba(255, 255, 255, 0.15);
      padding: 25px 30px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.25);
      width: 100%;
      display: flex;
      flex-direction: column;
    }
    .albums-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    .albums-header h3 {
      margin: 0;
      font-weight: 600;
      font-size: 1.3em;
      color: white;
    }
    .favorite-toggle {
      font-size: 1.7em;
      cursor: pointer;
      user-select: none;
      color: #ffd700;
      transition: color 0.3s;
      text-decoration: none;
    }
    .favorite-toggle:hover {
      color: #ffea00;
    }
    ul.album-list {
      list-style: none;
      padding-left: 0;
      margin: 0 0 15px 0;
      flex-grow: 1;
      overflow-y: auto;
    }
    ul.album-list li {
      background: rgba(255,255,255,0.25);
      padding: 12px 15px;
      border-radius: 12px;
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      transition: background 0.3s;
    }
    ul.album-list li:hover {
      background: rgba(255,255,255,0.4);
    }
    .fav-btn {
      background: transparent;
      border: none;
      cursor: pointer;
      font-size: 1.3em;
      color: #ffd700;
      transition: color 0.3s;
    }
    .fav-btn.not-fav {
      color: white;
      opacity: 0.6;
    }
    form.add-album-form {
      margin-top: 10px;
      display: flex;
      align-items: center;
    }
    form.add-album-form input[type="text"] {
      padding: 10px 15px;
      width: 70%;
      border-radius: 30px;
      border: none;
      outline: none;
      font-size: 1em;
      margin-right: 10px;
    }
    form.add-album-form button {
      padding: 10px 22px;
      border-radius: 30px;
      border: none;
      background: white;
      color: #3b82f6;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }
    form.add-album-form button:hover {
      background: #3b82f6;
      color: white;
    }
  </style>
</head>
<body>

<header>
  <div class="logo">
    <img src="logo.png" alt="SnapBox Logo" />
    <span class="welcome-text">Welcome, <?=htmlspecialchars($_SESSION['username'])?></span>
  </div>
  <form method="post" action="logout.php" style="margin:0;">
    <button type="submit" class="logout-btn">Logout</button>
  </form>
</header>

<main>
  <div class="albums-container">
    <div class="albums-header">
      <h3>Your Albums</h3>
      <a href="?show=<?= $show_favorites ? '' : 'favorites' ?>" class="favorite-toggle" title="<?= $show_favorites ? 'Show All Albums' : 'Show Favorite Albums' ?>">
        <?= $show_favorites ? '⭐' : '☆' ?>
      </a>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <ul class="album-list">
        <?php while ($row = $result->fetch_assoc()): ?>
          <li onclick="location.href='view_album.php?album_id=<?=intval($row['id'])?>'">
            <span><?=htmlspecialchars($row['album_name'] ?? 'Unnamed Album')?></span>
            <form method="get" style="margin:0;" onclick="event.stopPropagation();">
              <input type="hidden" name="toggle_fav" value="<?=intval($row['id'])?>" />
              <?php if ($show_favorites): ?>
                <input type="hidden" name="show" value="favorites" />
              <?php endif; ?>
              <button type="submit" class="fav-btn <?= $row['favorite'] ? '' : 'not-fav' ?>" title="Toggle Favorite">
                <?= $row['favorite'] ? '★' : '☆' ?>
              </button>
            </form>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p style="text-align:center;">You have no albums yet.</p>
    <?php endif; ?>

    <form class="add-album-form" method="post" action="">
      <input type="text" name="new_album_name" placeholder="New album name" required />
      <button type="submit">Add Album</button>
    </form>
  </div>
</main>

</body>
</html>