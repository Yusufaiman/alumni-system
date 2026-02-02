<?php
/**
 * SHARED LOGIC: PostJobAction
 * Handles the backend processing for job postings.
 * Should be included at the very top of pages to prevent "headers already sent" errors.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    $title = trim($_POST['title']);
    $company = trim($_POST['company']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $location = trim($_POST['location'] ?? '');
    $jobType = $_POST['jobType'] ?? 'FULL_TIME';

    if (empty($title) || empty($company) || empty($description)) {
        $errorMsg = "Please fill in all required fields.";
    } else {
        $userID = $_SESSION['userID'];
        $role = $_SESSION['role'];
        $csoID = null;

        // 1. If CSO, retrieve csoID from career_service_officer table
        if ($role === 'CAREER_SERVICE_OFFICER') {
            $csoStmt = $conn->prepare("SELECT csoID FROM career_service_officer WHERE userID = ? LIMIT 1");
            $csoStmt->bind_param("i", $userID);
            $csoStmt->execute();
            $csoRes = $csoStmt->get_result()->fetch_assoc();

            if ($csoRes) {
                $csoID = $csoRes['csoID'];
            } else {
                // 4. Prevent insertion if CSO profile is missing
                $errorMsg = "Error: Career Service Officer profile not found. Please complete your onboarding.";
            }
        }

        if (!isset($errorMsg)) {
            // 3. Use csoID for inserting into jobposting.careerOfficerID
            $sql = "INSERT INTO jobposting 
                    (careerOfficerID, postedByID, postedByRole, title, company, location, description, requirements, jobType, datePosted, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE, 'ACTIVE')";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisssssss", $csoID, $userID, $role, $title, $company, $location, $description, $requirements, $jobType);

            if ($stmt->execute()) {
                // Success - Redirect back to the caller's dashboard list
                $redirect = ($role === 'ALUMNI') ? 'job_opportunities.php' : 'job_applications.php';
                header("Location: " . $redirect . "?success=posted");
                exit();
            } else {
                $errorMsg = "Error posting job: " . $conn->error;
            }
        }
    }
}
?>