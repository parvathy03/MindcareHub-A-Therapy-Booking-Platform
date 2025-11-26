<?php
$conn = mysqli_connect("localhost", "root", "", "mindcare");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "SELECT * FROM articles ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Articles - MindCare</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4fc;
            padding: 40px;
        }

        h2 {
            color: #3f37c9;
            text-align: center;
            margin-bottom: 2rem;
        }

        table {
            width: 90%;
            margin: auto;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #4361ee;
            color: white;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        tr:hover {
            background: #f1f5ff;
        }

        .action-buttons a {
            padding: 6px 12px;
            margin-right: 8px;
            font-size: 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            transition: all 0.2s;
        }

        .view-btn {
            background-color: #4361ee;
        }

        .edit-btn {
            background-color: #10b981;
        }

        .delete-btn {
            background-color: #ef4444;
        }

        .action-buttons a:hover {
            opacity: 0.85;
        }
    </style>
</head>
<body>
    <h2>📰 All Articles</h2>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td class="action-buttons">
                            <a href="view_article.php?id=<?php echo $row['id']; ?>" class="view-btn">🔍 View</a>
                            <a href="edit_article.php?id=<?php echo $row['id']; ?>" class="edit-btn">✏️ Edit</a>
                            <a href="delete_article.php?id=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this article?');">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">No articles found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
