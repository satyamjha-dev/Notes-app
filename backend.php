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

    $delete = $conn->prepare("DELETE FROM notes WHERE id = ?");
    $delete->bind_param("i", $id);

    if ($delete->execute()) {
        echo json_encode(["status" => "success", "message" => "Note deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Delete failed"]);
    }

    $delete->close();
    $conn->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'] ?? null;
    $description = $_POST['description'] ?? null;

    if (!empty($title) && !empty($description)) {
        $insert = $conn->prepare("INSERT INTO notes (title, description) VALUES (?, ?)");
        $insert->bind_param("ss", $title, $description);

        if ($insert->execute()) {
            echo json_encode(["status" => "success", "message" => "Note inserted"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Insert failed"]);
        }

        $insert->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Missing title or description"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Only POST allowed"]);
}

$conn->close();

?>