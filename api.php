<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host     = "localhost";
$username = "root";
$password = "satyam@25";
$database = "notes";

$conn = @new mysqli($host, $username, $password);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . $conn->connect_error]);
    exit();
}

$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);

$conn->query("CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
)");

$action = $_REQUEST['action'] ?? '';


if ($action === 'get_notes') {
    $result = $conn->query("SELECT * FROM notes ORDER BY id DESC");
    $notes  = [];
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    echo json_encode(['success' => true, 'notes' => $notes]);
    exit();
}


if ($action === 'add_note' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($description)) {
        echo json_encode(['success' => false, 'error' => 'Title and description are required.']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO notes (title, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $description);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        echo json_encode(['success' => true, 'note' => ['id' => $new_id, 'title' => $title, 'description' => $description]]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add note.']);
    }
    exit();
}

if ($action === 'delete_note' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_id = (int)($_POST['note_id'] ?? 0);

    if ($note_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid note ID.']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM notes WHERE id = ?");
    $stmt->bind_param("i", $note_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete note.']);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);
