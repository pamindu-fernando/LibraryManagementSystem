<?php
// Ensure NO text or spaces appear before the <?php tag
include 'db_config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    
    $pdfPath = "";
    $newSize = 0;

    if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] === 0) {
        $fileName = time() . "_" . $_FILES['pdfFile']['name'];
        $target = "uploads/" . $fileName;
        
        if (move_uploaded_file($_FILES['pdfFile']['tmp_name'], $target)) {
            $pdfPath = $target;
            $newSize = filesize($target); // Calculate size for the dashboard
        }
    }

    $sql = "INSERT INTO books (title, author, category, quantity, available, file_path) 
            VALUES ('$title', '$author', '$category', 1, 1, '$pdfPath')";

    if (mysqli_query($conn, $sql)) {
        $response['success'] = true;
        $response['bookId'] = mysqli_insert_id($conn);
        $response['data'] = [
            'pdfFile' => $pdfPath,
            'size_bytes' => $newSize
        ];
    } else {
        $response['message'] = mysqli_error($conn);
    }
}
echo json_encode($response);