<?php
// Include config file for database connection
include 'config.php';

// Function to generate a unique ID
function generateUniqueID($conn) {
    $id = '';
    $isUnique = false;

    while (!$isUnique) {
        $randomLetters = chr(rand(97, 122)) . chr(rand(97, 122));
        $randomNumbers = sprintf('%04d', rand(0, 9999));
        $id = "TX" . substr(str_shuffle($randomLetters . $randomNumbers), 0, 6);

        $result = $conn->query("SELECT * FROM users WHERE id = '$id'");
        if ($result->num_rows == 0) {
            $isUnique = true;
        }
    }
    return $id;
}

// Function to synchronize the uploads folder with the database
function synchronizeUploads($conn) {
    $result = $conn->query("SELECT * FROM users");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offer_letter = $row['offer_letter'];
            $intern_certificate = $row['intern_certificate'];
            $transaction_proof = $row['transaction_proof'];

            // Check if offer letter exists
            if (!file_exists($offer_letter)) {
                $stmt = $conn->prepare("UPDATE users SET offer_letter = ? WHERE id = ?");
                $nullValue = null;
                $stmt->bind_param("ss", $nullValue, $row['id']);
                $stmt->execute();
                $stmt->close();
            }

            // Check if intern certificate exists
            if (!file_exists($intern_certificate)) {
                $stmt = $conn->prepare("UPDATE users SET intern_certificate = ? WHERE id = ?");
                $nullValue = null;
                $stmt->bind_param("ss", $nullValue, $row['id']);
                $stmt->execute();
                $stmt->close();
            }

            // Check if transaction proof exists
            if (!file_exists($transaction_proof)) {
                $stmt = $conn->prepare("UPDATE users SET transaction_proof = ? WHERE id = ?");
                $nullValue = null;
                $stmt->bind_param("ss", $nullValue, $row['id']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['name']) && !empty($_POST['mobile_number'])) {
    $id = generateUniqueID($conn);
    $name = $_POST['name'];
    $mobile_number = $_POST['mobile_number'];
    $whatsapp_no = $_POST['whatsapp_no'];
    $email = $_POST['email'];
    $dob = $_POST['dob'];
    $zipcode = $_POST['zipcode'];
    $doj = $_POST['doj'];
    $transaction_id = $_POST['transaction_id'];

    // Directories for file uploads
    $offer_letter_dir = "uploads/offer_letters/";
    $intern_certificate_dir = "uploads/intern_certificates/";
    $transaction_proof_dir = "uploads/transaction_proof/";

    // Initializing file paths as empty
    $offer_letter_path = "";
    $intern_certificate_path = "";
    $transaction_proof_path = "";

    if (!empty($_FILES['offer_letter']['name'])) {
        $offer_letter_path = $offer_letter_dir . $id . "_offer_" . basename($_FILES["offer_letter"]["name"]);
        move_uploaded_file($_FILES["offer_letter"]["tmp_name"], $offer_letter_path);
    }

    if (!empty($_FILES['intern_certificate']['name'])) {
        $intern_certificate_path = $intern_certificate_dir . $id . "_intern_" . basename($_FILES["intern_certificate"]["name"]);
        move_uploaded_file($_FILES["intern_certificate"]["tmp_name"], $intern_certificate_path);
    }

    if (!empty($_FILES['transaction_proof']['name'])) {
        $transaction_proof_path = $transaction_proof_dir . $id . "_proof_" . basename($_FILES["transaction_proof"]["name"]);
        move_uploaded_file($_FILES["transaction_proof"]["tmp_name"], $transaction_proof_path);
    }

    // Insert data into database
    $stmt = $conn->prepare("INSERT INTO users (id, name, mobile_number, whatsapp_no, email, dob, zipcode, doj, transaction_id, offer_letter, intern_certificate, transaction_proof) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssss", $id, $name, $mobile_number, $whatsapp_no, $email, $dob, $zipcode, $doj, $transaction_id, $offer_letter_path, $intern_certificate_path, $transaction_proof_path);

    if ($stmt->execute()) {
        echo "<p>User added successfully with ID: $id</p>";
    } else {
        echo "<p>Error: " . $stmt->error . "</p>";
    }
    $stmt->close();

    // Set session variable to prevent resubmission
    $_SESSION['form_submitted'] = true;
    header("Location: " . $_SERVER['PHP_SELF']); // Redirect to the same page
    exit;
}

// Call the synchronize function before fetching users
synchronizeUploads($conn);

// Fetch all users for displaying in the table
$users = [];
$result = $conn->query("SELECT * FROM users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Set table visibility based on user existence
$tableDisplayStyle = !empty($users) ? 'table' : 'none'; // Set initial visibility based on users
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        h2 {
            color: #333;
        }
        button {
            padding: 10px 15px;
            margin: 5px;
            border: none;
            background-color: #5bc0de;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #31b0d5;
        }
        #userForm {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="date"], input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            display: <?php echo $tableDisplayStyle; ?>; /* Set initial visibility from PHP */
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
            background-color: #fff;
        }
        th {
            background-color: #5bc0de;
            color: white;
        }
        tbody tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>

<h2>User Management</h2>
<button onclick="toggleUserForm()">Add Candidate Info</button>
<button onclick="toggleUserTable()">Show Candidate Info</button>

<div id="userForm" style="display:none;">
    <form method="POST" enctype="multipart/form-data">
        <label>Name:</label><input type="text" name="name" required>
        <label>Mobile Number:</label><input type="text" name="mobile_number" required>
        <label>WhatsApp No:</label><input type="text" name="whatsapp_no">
        <label>Email:</label><input type="email" name="email">
        <label>Date of Birth:</label><input type="date" name="dob">
        <label>Zipcode:</label><input type="text" name="zipcode">
        <label>Date of Joining:</label><input type="date" name="doj">
        <label>Transaction ID:</label><input type="text" name="transaction_id" required>
        <label>Offer Letter:</label><input type="file" name="offer_letter" accept=".pdf,.doc,.docx">
        <label>Intern Certificate:</label><input type="file" name="intern_certificate" accept=".pdf,.doc,.docx">
        <label>Transaction Proof:</label><input type="file" name="transaction_proof" accept=".pdf,.doc,.docx">
        <input type="submit" value="Submit">
    </form>
</div>

<table id="userTable">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Mobile Number</th>
            <th>WhatsApp No</th>
            <th>Email</th>
            <th>Date of Birth</th>
            <th>Zipcode</th>
            <th>Date of Joining</th>
            <th>Transaction ID</th>
            <th>Offer Letter</th>
            <th>Intern Certificate</th>
            <th>Transaction Proof</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo htmlspecialchars($user['id']); ?></td>
            <td><?php echo htmlspecialchars($user['name']); ?></td>
            <td><?php echo htmlspecialchars($user['mobile_number']); ?></td>
            <td><?php echo htmlspecialchars($user['whatsapp_no']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars($user['dob']); ?></td>
            <td><?php echo htmlspecialchars($user['zipcode']); ?></td>
            <td><?php echo htmlspecialchars($user['doj']); ?></td>
            <td><?php echo htmlspecialchars($user['transaction_id']); ?></td>
            <td><a href="<?php echo htmlspecialchars($user['offer_letter']); ?>" target="_blank">View</a></td>
            <td><a href="<?php echo htmlspecialchars($user['intern_certificate']); ?>" target="_blank">View</a></td>
            <td><a href="<?php echo htmlspecialchars($user['transaction_proof']); ?>" target="_blank">View</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
    // Function to toggle the user form
    function toggleUserForm() {
        var userForm = document.getElementById('userForm');
        userForm.style.display = userForm.style.display === 'none' ? 'block' : 'none';
    }

    // Function to toggle the user table
    function toggleUserTable() {
        var userTable = document.getElementById('userTable');
        userTable.style.display = userTable.style.display === 'table' ? 'none' : 'table';
    }

    // Show the user table if there are users initially
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($users)): ?>
            document.getElementById('userTable').style.display = 'table';
        <?php endif; ?>
    });
</script>

<?php
$conn->close();
?>
