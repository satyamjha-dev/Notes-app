<?php

$host = "localhost";
$user = "root";   
$pass = "satyam@25";       
$dbname = "test_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM notes WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Note deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Delete failed"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'] ?? null;
    $description = $_POST['description'] ?? null;

    if (!empty($title) && !empty($description)) {
        $stmt = $conn->prepare("INSERT INTO notes (title, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $description);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Note inserted"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Insert failed"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Missing title or description"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Only POST allowed"]);
}

$conn->close();

?>