<?php
// Database connection
$mysqli = new mysqli('localhost', 'root', '', 'my_home');

if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Get album_id from GET
$album_id = isset($_GET['album_id']) ? intval($_GET['album_id']) : 0;

if ($album_id <= 0) {
    die("Invalid album ID.");
}

// Get album name
$stmt = $mysqli->prepare("SELECT album_name FROM albums WHERE id = ?");
$stmt->bind_param('i', $album_id);
$stmt->execute();
$stmt->bind_result($album_name);
if (!$stmt->fetch()) {
    die("Album not found.");
}
$stmt->close();

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $tmp_name = $_FILES['photo']['tmp_name'];
    $file_name = basename($_FILES['photo']['name']);
    $target_path = $upload_dir . uniqid() . '_' . $file_name;
    
    if (move_uploaded_file($tmp_name, $target_path)) {
        $stmt = $mysqli->prepare("INSERT INTO photos (album_id, file_path) VALUES (?, ?)");
        $stmt->bind_param('is', $album_id, $target_path);
        $stmt->execute();
        $stmt->close();
        header("Location: view_album.php?album_id=$album_id");
        exit;
    } else {
        $upload_error = "Failed to upload photo.";
    }
}

// Handle photo deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_photos'])) {
    $delete_ids = $_POST['delete_photos'];
    if (is_array($delete_ids) && count($delete_ids) > 0) {
        // Prepare placeholders and types
        $placeholders = implode(',', array_fill(0, count($delete_ids), '?'));
        $types = str_repeat('i', count($delete_ids));

        // Combine params for SELECT
        $params = array_merge($delete_ids, [$album_id]);
        $types_all = $types . 'i';

        // Select file paths to delete files from disk
        $stmt = $mysqli->prepare("SELECT file_path FROM photos WHERE id IN ($placeholders) AND album_id = ?");
        $stmt->bind_param($types_all, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (file_exists($row['file_path'])) {
                unlink($row['file_path']);
            }
        }
        $stmt->close();

        // Delete records from DB
        $stmt = $mysqli->prepare("DELETE FROM photos WHERE id IN ($placeholders) AND album_id = ?");
        $stmt->bind_param($types_all, ...$params);
        $stmt->execute();
        $stmt->close();

        header("Location: view_album.php?album_id=$album_id");
        exit;
    }
}

// Fetch photos for this album
$stmt = $mysqli->prepare("SELECT id, file_path FROM photos WHERE album_id = ?");
$stmt->bind_param('i', $album_id);
$stmt->execute();
$photos_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Album - <?=htmlspecialchars($album_name)?></title>
<link href="https://fonts.googleapis.com/css2?family=Poiret+One&family=Quicksand:wght@400;600&display=swap" rel="stylesheet" />
<style>
  * {
    box-sizing: border-box;
  }
  body {
    margin: 0; padding: 0;
    font-family: 'Quicksand', sans-serif;
    background: linear-gradient(135deg, #6ee7b7, #3b82f6);
    color: white;
    min-height: 100vh;
  }
  header {
    padding: 20px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
  }
  header h2 {
    margin: 0;
  }
  .actions {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  /* Top-right select all checkbox */
  #selectAllWrapper {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 1.2em;
    cursor: pointer;
    user-select: none;
  }
  #selectAllCheckbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
  }
  /* Delete button styling */
  #deleteSelectedBtn {
    background: transparent;
    border: none;
    color: white;
    padding: 8px 12px;
    border-radius: 30px;
    font-weight: bold;
    font-size: 1em;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    transition: background 0.3s ease;
  }
  #deleteSelectedBtn:hover {
    background: rgba(0, 0, 0, 0.2);
  }
  #deleteSelectedBtn span {
    margin-left: 6px;
  }

  main {
    padding: 0 40px 40px;
  }
  .photos-container {
    margin-top: 25px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
  }
  .photo-item {
    position: relative;
    width: 160px;
    height: 120px;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    background: rgba(255,255,255,0.15);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  }
  .photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  /* Hidden checkboxes that show up when select all is clicked */
  .photo-checkbox {
    position: absolute;
    top: 10px;
    left: 10px;
    display: none;
    width: 30px;  /* Bigger checkbox */
    height: 30px; /* Bigger checkbox */
  }
  .photo-item.selected .photo-checkbox {
    display: block;
  }
  .hidden-checkbox {
    display: none;
  }

  /* Modal styles */
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    padding-top: 60px;
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.8);
  }
  .modal-content {
    margin: auto;
    display: block;
    max-width: 90%;
    max-height: 80vh;
    border-radius: 8px;
    box-shadow: 0 0 15px white;
  }
  .close-modal {
    position: absolute;
    top: 20px;
    right: 35px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    user-select: none;
  }

  /* Styled button for Upload Photo */
  .upload-form button {
    padding: 10px 20px;  /* Add padding for a bigger button */
    background-color: #4CAF50;  /* Green background */
    color: white;  /* White text */
    border: none;  /* Remove border */
    border-radius: 25px;  /* Round the corners */
    font-weight: bold;  /* Make text bold */
    cursor: pointer;  /* Show pointer cursor on hover */
    font-size: 1em;  /* Adjust font size */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);  /* Add a slight shadow */
    transition: background-color 0.3s ease, transform 0.3s ease;  /* Smooth hover effect */
  }

  .upload-form button:hover {
    background-color: #45a049;  /* Darker green on hover */
    transform: scale(1.05);  /* Slightly enlarge the button */
  }

  /* Back button styling */
  .back-btn {
    padding: 8px 16px;
    background-color: #FF6F61; /* Light red background */
    color: white;
    border: none;
    border-radius: 30px;
    font-weight: bold;
    cursor: pointer;
    font-size: 1em;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: background-color 0.3s ease, transform 0.3s ease;
  }

  .back-btn:hover {
    background-color: #FF4C39; /* Darker red on hover */
    transform: scale(1.05);
  }
</style>
</head>
<body>

<header>
  <h2><?=htmlspecialchars($album_name)?></h2>
  <form method="get" action="albums.php" style="margin: 0;">
    <button type="submit" class="back-btn">Back</button>
  </form>
  <form method="post" id="deleteForm" onsubmit="return confirm('Are you sure you want to delete selected photos?');" class="actions">
    <label id="selectAllWrapper" for="selectAllCheckbox" title="Select / Deselect All">
      <input type="checkbox" id="selectAllCheckbox" />
      Select All
    </label>

    <button id="deleteSelectedBtn" type="submit" name="delete_photos[]" value="">
      Delete
    </button>
  </form>
</header>

<main>

  <form method="post" enctype="multipart/form-data" class="upload-form" style="margin-bottom: 30px;">
    <input type="file" name="photo" accept="image/*" required />
    <button type="submit">Upload Photo</button>
  </form>

  <?php if ($photos_result->num_rows > 0): ?>
      <div class="photos-container">
        <?php while ($photo = $photos_result->fetch_assoc()): ?>
          <label class="photo-item" title="Click photo to view, or select checkbox">
            <input 
              type="checkbox" 
              name="delete_photos[]" 
              value="<?=intval($photo['id'])?>" 
              class="hidden-checkbox photo-checkbox" 
              onchange="updateDeleteButton()" />
            <img src="<?=htmlspecialchars($photo['file_path'])?>" alt="Photo" onclick="showModal('<?=htmlspecialchars($photo['file_path'], ENT_QUOTES)?>')" />
          </label>
        <?php endwhile; ?>
      </div>
  <?php else: ?>
    <p>No photos in this album yet.</p>
  <?php endif; ?>

</main>

<!-- Modal for full image -->
<div id="imgModal" class="modal" onclick="closeModal()">
  <span class="close-modal" onclick="closeModal()">&times;</span>
  <img class="modal-content" id="modalImg" />
</div>

<script>
  const selectAllCheckbox = document.getElementById('selectAllCheckbox');
  const deleteBtn = document.getElementById('deleteSelectedBtn');
  const photoItems = document.querySelectorAll('.photo-item');
  const photoCheckboxes = document.querySelectorAll('.photo-checkbox');

  // Toggle all photo checkboxes when select all changes
  selectAllCheckbox.addEventListener('change', () => {
    const checked = selectAllCheckbox.checked;
    photoCheckboxes.forEach(cb => cb.checked = checked);
    photoItems.forEach(item => item.classList.toggle('selected', checked));
    updateDeleteButton();
  });

  // Update delete button visibility & values
  function updateDeleteButton() {
    const checkedBoxes = [...photoCheckboxes].filter(cb => cb.checked);
    deleteBtn.style.display = checkedBoxes.length > 0 ? 'flex' : 'none';
    deleteBtn.value = '';
  }

  function showModal(src) {
    const modal = document.getElementById('imgModal');
    const modalImg = document.getElementById('modalImg');
    modal.style.display = 'block';
    modalImg.src = src;
  }

  function closeModal() {
    const modal = document.getElementById('imgModal');
    modal.style.display = 'none';
  }

  // Prevent modal close when clicking image
  document.getElementById('modalImg').addEventListener('click', e => e.stopPropagation());
</script>

</body>
</html>
