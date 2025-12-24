<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$db_file = 'participants.db';

// Initialize SQLite database
function initDatabase() {
    global $db_file;
    
    try {
        $pdo = new PDO("sqlite:$db_file");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create participants table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS participants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            team_name TEXT NOT NULL,
            domain TEXT NOT NULL,
            lead_name TEXT NOT NULL,
            lead_usn TEXT NOT NULL,
            lead_gender TEXT NOT NULL,
            lead_mobile TEXT NOT NULL,
            lead_email TEXT NOT NULL,
            college_name TEXT NOT NULL,
            member2_name TEXT,
            member2_usn TEXT,
            member2_gender TEXT,
            member2_mobile TEXT,
            member3_name TEXT,
            member3_usn TEXT,
            member3_gender TEXT,
            member3_mobile TEXT,
            member4_name TEXT,
            member4_usn TEXT,
            member4_gender TEXT,
            member4_mobile TEXT,
            member5_name TEXT,
            member5_usn TEXT,
            member5_gender TEXT,
            accommodation TEXT NOT NULL,
            registration_date DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        return $pdo;
    } catch (Exception $e) {
        error_log("Database initialization error: " . $e->getMessage());
        throw $e;
    }
}

// Get all participants
function getParticipants() {
    try {
        $pdo = initDatabase();
        
        // First, let's check if the table exists and has data
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM participants");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT * FROM participants ORDER BY registration_date DESC");
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $participants,
            'debug' => [
                'table_count' => $count['count'],
                'db_file_exists' => file_exists($GLOBALS['db_file']),
                'db_file_size' => file_exists($GLOBALS['db_file']) ? filesize($GLOBALS['db_file']) : 0
            ]
        ];
    } catch (Exception $e) {
        error_log("Get participants error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => [
                'db_file_exists' => file_exists($GLOBALS['db_file']),
                'db_file_size' => file_exists($GLOBALS['db_file']) ? filesize($GLOBALS['db_file']) : 0
            ]
        ];
    }
}

// Save participant registration
function saveParticipant($data) {
    try {
        $pdo = initDatabase();
        
        $sql = "INSERT INTO participants (
            team_name, domain, lead_name, lead_usn, lead_gender, lead_mobile, lead_email, college_name,
            member2_name, member2_usn, member2_gender, member2_mobile,
            member3_name, member3_usn, member3_gender, member3_mobile,
            member4_name, member4_usn, member4_gender, member4_mobile,
            member5_name, member5_usn, member5_gender, accommodation
        ) VALUES (
            :team_name, :domain, :lead_name, :lead_usn, :lead_gender, :lead_mobile, :lead_email, :college_name,
            :member2_name, :member2_usn, :member2_gender, :member2_mobile,
            :member3_name, :member3_usn, :member3_gender, :member3_mobile,
            :member4_name, :member4_usn, :member4_gender, :member4_mobile,
            :member5_name, :member5_usn, :member5_gender, :accommodation
        )";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':team_name' => $data['teamName'] ?? '',
            ':domain' => $data['domain'] ?? '',
            ':lead_name' => $data['leadName'] ?? '',
            ':lead_usn' => $data['leadUsn'] ?? '',
            ':lead_gender' => $data['leadGender'] ?? '',
            ':lead_mobile' => $data['leadMobile'] ?? '',
            ':lead_email' => $data['leadEmail'] ?? '',
            ':college_name' => $data['collegeName'] ?? '',
            ':member2_name' => $data['member2Name'] ?? null,
            ':member2_usn' => $data['member2Usn'] ?? null,
            ':member2_gender' => $data['member2Gender'] ?? null,
            ':member2_mobile' => $data['member2Mobile'] ?? null,
            ':member3_name' => $data['member3Name'] ?? null,
            ':member3_usn' => $data['member3Usn'] ?? null,
            ':member3_gender' => $data['member3Gender'] ?? null,
            ':member3_mobile' => $data['member3Mobile'] ?? null,
            ':member4_name' => $data['member4Name'] ?? null,
            ':member4_usn' => $data['member4Usn'] ?? null,
            ':member4_gender' => $data['member4Gender'] ?? null,
            ':member4_mobile' => $data['member4Mobile'] ?? null,
            ':member5_name' => $data['member5Name'] ?? null,
            ':member5_usn' => $data['member5Usn'] ?? null,
            ':member5_gender' => $data['member5Gender'] ?? null,
            ':accommodation' => $data['accommodation'] ?? ''
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Registration saved successfully',
                'id' => $pdo->lastInsertId(),
                'debug' => [
                    'data_received' => $data
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to execute insert statement'
            ];
        }
    } catch (Exception $e) {
        error_log("Save participant error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => [
                'data_received' => $data
            ]
        ];
    }
}

// Handle requests
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(getParticipants());
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    } else {
        echo json_encode(saveParticipant($input));
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>