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
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
       .left
       {
        margin-left: 20px;
       }
        </style>
</head>
<body>

<div class="left">
    <h2 class="my-4">User Management</h2>
    <button class="btn btn-primary" id="toggleFormButton" onclick="toggleUserForm()">Add Candidate Info</button>
<button class="btn btn-info" id="toggleTableButton" onclick="toggleUserTable()">Show Candidate Info</button>

    <div id="userForm" style="display:none;" class="mt-4">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="userId" id="userId" value="">
            <input type="hidden" name="editFlag" id="editFlag" value="false">
            <div class="form-group">
                <label for="userName">Name:</label>
                <input type="text" class="form-control" name="name" id="userName" required>
            </div>
            <div class="form-group">
                <label for="userMobile">Mobile Number:</label>
                <input type="text" class="form-control" name="mobile_number" id="userMobile" required>
            </div>
            <div class="form-group">
                <label for="userWhatsApp">WhatsApp No:</label>
                <input type="text" class="form-control" name="whatsapp_no" id="userWhatsApp">
            </div>
            <div class="form-group">
                <label for="userEmail">Email:</label>
                <input type="email" class="form-control" name="email" id="userEmail">
            </div>
            <div class="form-group">
                <label for="userDOB">Date of Birth:</label>
                <input type="date" class="form-control" name="dob" id="userDOB">
            </div>
            <div class="form-group">
                <label for="userCity">City:</label>
                <input type="text" class="form-control" name="city" id="userCity" required>
            </div>
            <div class="form-group">
                <label for="userZipcode">Zipcode:</label>
                <input type="text" class="form-control" name="zipcode" id="userZipcode" required>
            </div>
            <div class="form-group">
                <label for="userDOJ">Date of Joining:</label>
                <input type="date" class="form-control" name="doj" id="userDOJ">
            </div>
            <div class="form-group">
                <label for="userTransactionID">Transaction ID:</label>
                <input type="text" class="form-control" name="transaction_id" id="userTransactionID">
            </div>
            <div class="form-group">
                <label for="offerLetter">Offer Letter:</label>
                <input type="file" class="form-control-file" name="offer_letter" accept=".pdf,.doc,.docx">
            </div>
            <div class="form-group">
                <label for="internCertificate">Intern Certificate:</label>
                <input type="file" class="form-control-file" name="intern_certificate" accept=".pdf,.doc,.docx">
            </div>
            <div class="form-group">
                <label for="transactionProof">Transaction Proof:</label>
                <input type="file" class="form-control-file" name="transaction_proof" accept=".pdf,.doc,.docx">
            </div>
            <input type="submit" class="btn btn-success" value="Submit"> <br > <br >
        </form>
    </div>

    <table id="userTable" style="display:none;" class="table table-striped mt-4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>WhatsApp</th>
                <th>Email</th>
                <th>D.O.B</th>
                <th>City</th>
                <th>Zipcode</th>
                <th>D.O.J</th>
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
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo $user['name']; ?></td>
                    <td><?php echo $user['mobile_number']; ?></td>
                    <td><?php echo $user['whatsapp_no']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['dob']; ?></td>
                    <td><?php echo $user['city']; ?></td>
                    <td><?php echo $user['zipcode']; ?></td>
                    <td><?php echo $user['doj']; ?></td>
                    <td><?php echo $user['transaction_id']; ?></td>
                    <td><a href="<?php echo $user['offer_letter']; ?>" target="_blank">View</a></td>
                    <td><a href="<?php echo $user['intern_certificate']; ?>" target="_blank">View</a></td>
                    <td><a href="<?php echo $user['transaction_proof']; ?>" target="_blank">View</a></td>
                    <td class="actions"><center>
                        <button class="btn btn-warning" onclick="editUser('<?php echo $user['id']; ?>')">Edit <br /></button><br />
                        <button class="btn btn-danger" onclick="deleteUser('<?php echo $user['id']; ?>')"> Delete</button><br />
                        </center></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
function toggleUserForm() {
    var form = document.getElementById('userForm');
    var table = document.getElementById('userTable');
    var button = document.getElementById('toggleFormButton');

    if (form.style.display === 'none') {
        form.style.display = 'block';
        table.style.display = 'none';
        button.innerText = "Close Form";
    } else {
        form.style.display = 'none';
        button.innerText = "Add Candidate Info";
    }
}

function toggleUserTable() {
    var form = document.getElementById('userForm');
    var table = document.getElementById('userTable');
    var button = document.getElementById('toggleTableButton');

    if (table.style.display === 'none') {
        table.style.display = 'table';
        form.style.display = 'none';
        button.innerText = "Close Table";
    } else {
        table.style.display = 'none';
        button.innerText = "Show Candidate Info";
    }
}


function editUser(id) {
    // Fetch user data and populate the form
    // This is a simplified example; you'll want to fetch data from your database in a real application
    var user = <?php echo json_encode($users); ?>.find(user => user.id === id);
    document.getElementById('userId').value = user.id;
    document.getElementById('userName').value = user.name;
    document.getElementById('userMobile').value = user.mobile_number;
    document.getElementById('userWhatsApp').value = user.whatsapp_no;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userDOB').value = user.dob;
    document.getElementById('userCity').value = user.city;
    document.getElementById('userZipcode').value = user.zipcode;
    document.getElementById('userDOJ').value = user.doj;
    document.getElementById('userTransactionID').value = user.transaction_id;
    document.getElementById('editFlag').value = 'true';
    toggleUserForm();
}

function deleteUser(id) {
    if (confirm("Are you sure you want to delete this user?")) {
        window.location.href = 'delete_user.php?id=' + id;
    }
}
</script>

</body>
</html>
