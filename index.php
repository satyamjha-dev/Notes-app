<?php
// index.php
$host = "localhost";
$username = "root";
$password = "satyam@25"; 
$database = "notes";

$conn = @new mysqli($host, $username, $password);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . " (Please ensure MySQL is running)");
}


$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);


$table_query = "CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
)";
$conn->query($table_query);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $title = trim($conn->real_escape_string($_POST['title']));
    $description = trim($conn->real_escape_string($_POST['description']));
    
    if (!empty($title) && !empty($description)) {
        $sql = "INSERT INTO notes (title, description) VALUES ('$title', '$description')";
        $conn->query($sql);
    }
    // Redirect to avoid form resubmission on page refresh (Post/Redirect/Get pattern)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle note deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note'])) {
    $note_id = (int)$_POST['note_id'];
    $sql = "DELETE FROM notes WHERE id = $note_id";
    $conn->query($sql);
    
    // Redirect after deletion
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all notes from the database, newest first
$result = $conn->query("SELECT * FROM notes ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes App</title>
    <!-- Importing modern font for a premium look -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="app-header">
            <h1>Notes App</h1>
            <p>A simple and elegant way to keep your thoughts organized.</p>
        </header>

        <!-- Add Note Form -->
        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="note-form">
                <div class="input-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" placeholder="E.g., Grocery List" required>
                </div>
                
                <div class="input-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Write your note here..." rows="4" required></textarea>
                </div>

                <button type="submit" name="add_note" class="btn-add">Add Note</button>
            </form>
        </div>

        <!-- Display Notes Section -->
        <div class="notes-section">
            <h2>All Notes</h2>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="notes-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="note-card">
                            <div class="note-content">
                                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                            </div>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="delete-form">
                                <input type="hidden" name="note_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_note" class="btn-delete">Delete</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No notes found. Add a note to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
