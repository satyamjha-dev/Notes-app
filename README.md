# AI Task Assignment Integration (Google Gemini)

This project integrates the Google Gemini Pro API into a PHP-based Task/Notes application to automatically assign tasks to team members.

## Features

- **Automated Assignment**: Uses Google Gemini Pro to decide which team member is best suited for a task.
- **Skill Matching**: The AI analyzes the `required_skill` and compares it with team members' profiles.
- **Workload Awareness**: Matches based on lower workload percentages.
- **JSON Formatting**: Prompted to return strict JSON for seamless integration.
- **Database Integration**: Assignments are stored in a dedicated `task_assignments` table.

## How it Works

1. **Input**: User provides a Task Title and a "Required Skill".
2. **AI Processing**:
   - PHP fetches the current team list from MySQL.
   - A structured prompt is sent to Google Gemini Pro API via cURL.
   - The script parses the response from `candidates[0].content.parts[0].text`.
3. **Storage**: The assignment details are saved to the `task_assignments` table.
4. **Output**: The assigned person and the AI's reasoning are displayed on the UI.

## Setup Instructions

### 1. Gemini API Key
Open `assign.php` and replace the placeholder with your actual Google Gemini API Key:
```php
$gemini_api_key = 'your_gemini_api_key_here';
```

### 2. Database Schema
The system automatically creates the `task_assignments` table. Ensure you have team members in your `team_members` table.

### 3. Files Modified
- `assign.php`: **UPDATED** to use Google Gemini Pro API.
- `index.php`: UI for task input and assignment display.
- `api.php`: Database connection and note management.

## Demo Usage
1. Enter "Design Logo" in the Title.
2. Enter "UI/UX" in the Required Skill.
3. Click **Auto-Assign Task (AI)**.
4. The AI will select the best member and show the reason!
