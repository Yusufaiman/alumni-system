<?php
/**
 * STUDENT EVENT LIST PAGE
 * This page displays all available events and allows students to register.
 */

session_start();
require_once('../../config/db.php');

// Check if user is logged in and is a STUDENT
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../../auth/login.php");
    exit();
}

// Fetch all events from the event table using prepared statements
$query = "SELECT eventID, title, description, eventDate, location FROM event ORDER BY eventDate ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Error fetching events: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Available Events - Alumni Engagement System</title>
    <style>
        /* Basic styling for readability */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .btn {
            padding: 8px 12px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .nav-links {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="nav-links">
        <a href="../dashboard.php">Back to Dashboard</a>
    </div>

    <h2>Upcoming Events</h2>
    <p>View and register for upcoming university events.</p>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Date</th>
                <th>Location</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['eventDate']); ?></td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <td>
                            <!-- Registration Form -->
                            <form action="register_event.php" method="POST">
                                <input type="hidden" name="eventID" value="<?php echo $row['eventID']; ?>">
                                <button type="submit" class="btn">Register</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No events found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>