<?php
// Include PHPMailer namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Autoload PHPMailer classes
require 'phpmailer/vendor/autoload.php';

// Database connection setup
$servername = "localhost"; // Database server
$username = "root"; // Database username (update if different)
$password = ""; // Database password (update if different)
$dbname = "exrecise5"; // Your database name

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the table if it doesn't exist
$tableCreationQuery = "
CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    tel VARCHAR(20) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female', 'Other', 'Unknown') NOT NULL,
    hobbies TEXT,
    message TEXT,
    file_path VARCHAR(255),
    password_hash VARCHAR(255),
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

// Execute the table creation query
if ($conn->query($tableCreationQuery) === TRUE) {
    echo "Table 'form_submissions' is ready to use.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Ensure form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $tel = htmlspecialchars($_POST['tel']);
    $age = (int) $_POST['age'];
    $gender = in_array($_POST['gender'], ['Male', 'Female', 'Other']) ? $_POST['gender'] : 'Unknown';
    $hobbies = isset($_POST['hobbies']) ? implode(", ", $_POST['hobbies']) : "None";
    $message = htmlspecialchars($_POST['message']);
    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password

    $fileMessage = "No file uploaded.";
    $filePath = "";

    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxFileSize = 2 * 1024 * 1024; // 2 MB

        if (in_array($_FILES['file']['type'], $allowedTypes) && $_FILES['file']['size'] <= $maxFileSize) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmpPath = $_FILES['file']['tmp_name'];
            $fileName = basename($_FILES['file']['name']);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($fileTmpPath, $filePath)) {
                $fileMessage = "File successfully uploaded to: $filePath";
            } else {
                $fileMessage = "Error uploading file.";
            }
        } else {
            $fileMessage = "Invalid file type or size exceeded.";
        }
    }

    // Insert data into the database
    $stmt = $conn->prepare("INSERT INTO form_submissions (name, email, tel, age, gender, hobbies, message, file_path, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiissss", $name, $email, $tel, $age, $gender, $hobbies, $message, $filePath, $passwordHash);

    if ($stmt->execute()) {
        $dbStatus = "Data successfully saved to the database.";
    } else {
        $dbStatus = "Error saving data to the database: " . $conn->error;
    }
    $stmt->close();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'pinkymoran088@gmail.com';
        $mail->Password = 'gubl ddog cfsh ngfn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('pinkymoran088@gmail.com', 'Pinki website');
        $mail->addAddress($email, $name);
        $mail->Subject = 'Form Submission Confirmation';
        $mail->Body = "Hi $name,\n\nThank you for submitting the form...\n\n$fileMessage\n\nRegards,\n Pinki Website Team";
        $mail->send();
        $emailStatus = "A confirmation email has been sent to $email.";
    } catch (Exception $e) {
        $emailStatus = "Failed to send confirmation email. Error: " . $mail->ErrorInfo;
    }

    // Display submission details
    echo "<h2>Form Submission Details:</h2>";
    echo "<p><strong>Name:</strong> $name</p>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Telephone:</strong> $tel</p>";
    echo "<p><strong>Age:</strong> $age</p>";
    echo "<p><strong>Gender:</strong> $gender</p>";
    echo "<p><strong>Hobbies:</strong> $hobbies</p>";
    echo "<p><strong>Message:</strong> $message</p>";
    echo "<p><strong>File Upload Status:</strong> $fileMessage</p>";
    echo "<p><strong>Email Status:</strong> $emailStatus</p>";
    echo "<p><strong>Database Status:</strong> $dbStatus</p>";
} else {
    echo "Invalid request method.";
}

// Close the database connection
$conn->close();
?>
