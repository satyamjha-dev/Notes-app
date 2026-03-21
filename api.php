<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// --- DATABASE CONNECTION ---
$host = "localhost";
$user = "root";
$pass = "satyam@25";
$db   = "notes";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => "DB Connection Failed: " . $conn->connect_error]);
    exit;
}

// Create DB if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// Create table if not exists (REQ 1)
$table_sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    required_skill VARCHAR(100),
    assigned_to VARCHAR(100),
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_sql);

// --- GEMINI API INTEGRATION (REQ 3 & 4) ---
function assignTaskAI($title, $description, $skill) {
    $api_key = "AIzaSyBPioFi4rU605OxShsozgNXrUnF35MT-Rs"; 
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $api_key;

    $team_data = "Rahul - Frontend - 40%\nAman - Backend - 20%";

    $prompt = "You are an AI project manager.

Assign the task to the best team member based on:
- skill match
- workload (prefer lower workload)

Task: $title
Description: $description
Required Skill: $skill

Team:
$team_data

Rules:
- Assign only one person
- Prefer exact skill match
- Prefer lower workload
- Return ONLY JSON

{
  \"assigned_to\": \"name\",
  \"reason\": \"short explanation\"
}";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || !$response) {
        return ['assigned_to' => 'Not Assigned', 'reason' => 'AI connection failed.'];
    }

    $resData = json_decode($response, true);
    $aiText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';

    // Parse JSON safely (REQ 4)
    // Extract everything between the first '{' and the last '}'
    $start = strpos($aiText, '{');
    $end   = strrpos($aiText, '}');
    
    if ($start !== false && $end !== false) {
        $jsonStr = substr($aiText, $start, $end - $start + 1);
        $result = json_decode($jsonStr, true);
    } else {
        $result = null;
    }

    if (!$result || !isset($result['assigned_to'])) {
        return ['assigned_to' => 'Not Assigned', 'reason' => 'AI returned invalid format: ' . substr(strip_tags($aiText), 0, 50)];
    }

    return [
        'assigned_to' => $result['assigned_to'] ?? 'Not Assigned',
        'reason'      => $result['reason'] ?? 'No reason available'
    ];
}

// --- API ACTIONS ---
$action = $_REQUEST['action'] ?? '';

if ($action === 'add_note' || $action === 'add_task') {
    $title = $_POST['title'] ?? '';
    $desc  = $_POST['description'] ?? '';
    $skill = $_POST['required_skill'] ?? 'General';

    if (!$title) {
        echo json_encode(['success' => false, 'error' => 'Title is required.']);
        exit;
    }

    // AI Assignment
    $assignment = assignTaskAI($title, $desc, $skill);

    $stmt = $conn->prepare("INSERT INTO tasks (title, description, required_skill, assigned_to, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $desc, $skill, $assignment['assigned_to'], $assignment['reason']);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'note' => [
                'id' => $new_id, 
                'title' => $title, 
                'description' => $desc, 
                'required_skill' => $skill,
                'assigned_to' => $assignment['assigned_to'],
                'reason' => $assignment['reason']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();

} elseif ($action === 'get_notes' || $action === 'get_tasks') {
    $result = $conn->query("SELECT * FROM tasks ORDER BY id DESC");
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    echo json_encode(['success' => true, 'notes' => $tasks]);

} elseif ($action === 'delete_note') {
    $id = $_POST['note_id'] ?? 0;
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete.']);
    }
    $stmt->close();

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action.']);
}

$conn->close();
?>
