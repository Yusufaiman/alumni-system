<?php
/**
 * CAREER SERVICE OFFICER - CREATE EVENT
 */
session_start();
require_once "../../config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: /alumni-system/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $location = trim($_POST['location']);
    $capacity = intval($_POST['capacity']);
    $userID = $_SESSION['userID'];

    if (empty($title) || empty($description) || empty($date) || empty($location) || $capacity <= 0) {
        $errorMsg = "Please fill in all fields correctly.";
    } else {
        $sql = "INSERT INTO event (title, description, date, location, capacity, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, 'OPEN')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $title, $description, $date, $location, $capacity, $userID);

        if ($stmt->execute()) {
            header("Location: manage_events.php?success=created");
            exit();
        } else {
            $errorMsg = "Error creating event: " . $conn->error;
        }
    }
}

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event | CSO Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .manage-events-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .form-card {
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            box-shadow: var(--card-shadow);
            max-width: 700px;
            margin: 0 auto;
        }

        .header-section {
            margin-bottom: 2.5rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            border: 1.5px solid var(--border-light);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
            background: #F9FAFB;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .btn-submit {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 1.25rem;
            border-radius: 0.75rem;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            background: #1D4ED8;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);
        }

        .error-banner {
            background: #FEF2F2;
            color: #991B1B;
            padding: 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            font-weight: 600;
            border: 1px solid #FEE2E2;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="manage-events-container">
        <!-- Breadcrumb / Back Link -->
        <div style="margin-bottom: 1.5rem;">
            <a href="manage_events.php"
                style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Manage Events
            </a>
        </div>

        <div class="form-card">
            <div class="header-section">
                <h1
                    style="font-size: 2rem; color: var(--text-dark); margin-bottom: 0.75rem; font-weight: 800; letter-spacing: -0.025em;">
                    Create New Event</h1>
                <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.5;">Schedule a new alumni or student
                    event by providing the details below.</p>
            </div>

            <?php if (isset($errorMsg)): ?>
                <div class="error-banner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo $errorMsg; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="title">Event Title</label>
                    <input type="text" name="title" id="title" class="form-control"
                        placeholder="e.g. Alumni Networking Night 2026" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div class="form-group">
                        <label for="date">Event Date</label>
                        <input type="date" name="date" id="date" class="form-control" required
                            min="<?php echo date("Y-m-d"); ?>">
                    </div>

                    <div class="form-group">
                        <label for="capacity">Max Capacity</label>
                        <input type="number" name="capacity" id="capacity" class="form-control" placeholder="e.g. 100"
                            required min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" name="location" id="location" class="form-control"
                        placeholder="e.g. Grand Hall / Zoom Link" required>
                </div>

                <div class="form-group">
                    <label for="description">Event Description</label>
                    <textarea name="description" id="description" class="form-control" rows="6"
                        placeholder="Provide details about the event goals, itinerary, and why students should attend..."
                        required></textarea>
                </div>

                <button type="submit" name="create_event" class="btn-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Create Event
                </button>
            </form>
        </div>
    </div>

    <!-- Dropdown Interaction Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const profileBtn = document.getElementById('profileBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });
                document.addEventListener('click', function (e) {
                    if (!profileDropdown.contains(e.target) && !profileBtn.contains(e.target)) {
                        profileDropdown.classList.remove('show');
                    }
     });
            }
        });
    </script>
</body>

</html>