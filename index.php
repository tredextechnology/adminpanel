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

// Handle form submission
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['name']) && !empty($_POST['mobile_number'])) {
    $id = $_POST['userId']; // Get the user ID from the form
    $name = $_POST['name'];
    $mobile_number = $_POST['mobile_number'];
    $whatsapp_no = $_POST['whatsapp_no'];
    $email = $_POST['email'];
    $dob = $_POST['dob'];
    $city = $_POST['city'];
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
        $transaction_proof_path = $transaction_proof_dir . $id . "_transaction_" . basename($_FILES["transaction_proof"]["name"]);
        move_uploaded_file($_FILES["transaction_proof"]["tmp_name"], $transaction_proof_path);
    }

    // Check if we are editing an existing user
    if ($_POST['editFlag'] == 'true') {
        // Update existing user in the database
        $stmt = $conn->prepare("UPDATE users SET name=?, mobile_number=?, whatsapp_no=?, email=?, dob=?, city=?, zipcode=?, doj=?, transaction_id=?, offer_letter=?, intern_certificate=?, transaction_proof=? WHERE id=?");
        $stmt->bind_param("sssssssssssss", $name, $mobile_number, $whatsapp_no, $email, $dob, $city, $zipcode, $doj, $transaction_id, $offer_letter_path, $intern_certificate_path, $transaction_proof_path, $id);
    } else {
        // Insert new user into the database
        $id = generateUniqueID($conn); // Ensure ID is generated for new users
        $stmt = $conn->prepare("INSERT INTO users (id, name, mobile_number, whatsapp_no, email, dob, city, zipcode, doj, transaction_id, offer_letter, intern_certificate, transaction_proof) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssss", $id, $name, $mobile_number, $whatsapp_no, $email, $dob, $city, $zipcode, $doj, $transaction_id, $offer_letter_path, $intern_certificate_path, $transaction_proof_path);
    }

    if ($stmt->execute()) {
        echo "<p>User added/updated successfully with ID: $id</p>";
    } else {
        echo "<p>Error: " . $stmt->error . "</p>";
    }
    $stmt->close();

    // Set session variable to prevent resubmission
    $_SESSION['form_submitted'] = true;
    header("Location: " . $_SERVER['PHP_SELF']); // Redirect to the same page
    exit;
}


// Fetch users from database
$users = [];
$result = $conn->query("SELECT * FROM users");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

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
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table, th, td {
    border: 1px solid black;
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background-color: #f2f2f2;
}

input[type="text"], input[type="email"], input[type="date"], input[type="file"] {
    width: 100%;
    padding: 8px;
    margin: 5px 0;
    box-sizing: border-box;
}

button {
    background-color: #4CAF50; /* Green */
    color: white;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
}

button:hover {
    background-color: #45a049;
}

    </style>
</head>
<body>

<h2>User Management</h2>
<button onclick="toggleUserForm()">Add Candidate Info</button>
<button onclick="toggleUserTable()">Show Candidate Info</button>

<div id="userForm" style="display:none;">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="userId" id="userId" value="">
        <input type="hidden" name="editFlag" id="editFlag" value="false">
        <label>Name:</label><input type="text" name="name" id="userName" required><br>
        <label>Mobile Number:</label><input type="text" name="mobile_number" id="userMobile" required><br>
        <label>WhatsApp No:</label><input type="text" name="whatsapp_no" id="userWhatsApp"><br>
        <label>Email:</label><input type="email" name="email" id="userEmail"><br>
        <label>Date of Birth:</label><input type="date" name="dob" id="userDOB"><br>
        <label>City:</label><input type="text" name="city" id="userCity" required><br>
        <label>Zipcode:</label><input type="text" name="zipcode" id="userZipcode" required><br>
        <label>Date of Joining:</label><input type="date" name="doj" id="userDOJ"><br>
        <label>Transaction ID:</label><input type="text" name="transaction_id" id="userTransactionID"><br>
        <label>Offer Letter:</label><input type="file" name="offer_letter" accept=".pdf,.doc,.docx"><br>
        <label>Intern Certificate:</label><input type="file" name="intern_certificate" accept=".pdf,.doc,.docx"><br>
        <label>Transaction Proof:</label><input type="file" name="transaction_proof" accept=".pdf,.doc,.docx"><br>
        <input type="submit" value="Submit">
    </form>
</div>

<table id="userTable" style="display:none;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Mobile Number</th>
            <th>WhatsApp No</th>
            <th>Email</th>
            <th>Date of Birth</th>
            <th>City</th>
            <th>Zipcode</th>
            <th>Date of Joining</th>
            <th>Transaction ID</th>
            <th>Offer Letter</th>
            <th>Intern Certificate</th>
            <th>Transaction Proof</th>
            <th>Actions</th>
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
            <td><?php echo htmlspecialchars($user['city']); ?></td>
            <td><?php echo htmlspecialchars($user['zipcode']); ?></td>
            <td><?php echo htmlspecialchars($user['doj']); ?></td>
            <td><?php echo htmlspecialchars($user['transaction_id']); ?></td>
            <td><a href="<?php echo htmlspecialchars($user['offer_letter']); ?>">View</a></td>
            <td><a href="<?php echo htmlspecialchars($user['intern_certificate']); ?>">View</a></td>
            <td><a href="<?php echo htmlspecialchars($user['transaction_proof']); ?>">View</a></td>
            <td>
                <button onclick="editUser('<?php echo htmlspecialchars($user['id']); ?>', '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo htmlspecialchars($user['mobile_number']); ?>', '<?php echo htmlspecialchars($user['whatsapp_no']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['dob']); ?>', '<?php echo htmlspecialchars($user['city']); ?>', '<?php echo htmlspecialchars($user['zipcode']); ?>', '<?php echo htmlspecialchars($user['doj']); ?>',                '<?php echo htmlspecialchars($user['transaction_id']); ?>')">Edit</button>
                <a href="delete_user.php?id=<?php echo htmlspecialchars($user['id']); ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
    // Function to show/hide the user form
    function toggleUserForm() {
        var userForm = document.getElementById('userForm');
        userForm.style.display = userForm.style.display === 'none' ? 'block' : 'none';
        // Clear form fields
        if (userForm.style.display === 'block') {
            document.getElementById('userForm').reset();
            document.getElementById('editFlag').value = 'false';
        }
    }

    // Function to show/hide the user table
    function toggleUserTable() {
        var userTable = document.getElementById('userTable');
        userTable.style.display = userTable.style.display === 'none' ? 'table' : 'none';
    }

    // Function to populate the form with user data for editing
    function editUser(id, name, mobile, whatsapp, email, dob, city, zipcode, doj, transaction_id) {
        document.getElementById('userId').value = id;
        document.getElementById('userName').value = name;
        document.getElementById('userMobile').value = mobile;
        document.getElementById('userWhatsApp').value = whatsapp;
        document.getElementById('userEmail').value = email;
        document.getElementById('userDOB').value = dob;
        document.getElementById('userCity').value = city;
        document.getElementById('userZipcode').value = zipcode;
        document.getElementById('userDOJ').value = doj;
        document.getElementById('userTransactionID').value = transaction_id;
        document.getElementById('editFlag').value = 'true';
        toggleUserForm();
    }
</script>

<?php
$conn->close();
?>

