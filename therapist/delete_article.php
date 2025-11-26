<?php
$conn = mysqli_connect("localhost", "root", "", "mindcare");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM articles WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        header("Location: articles.php");
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
} else {
    header("Location: articles.php");
    exit();
}
?>
