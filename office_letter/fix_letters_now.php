<?php
require_once 'config/db.php';

echo "<h2>Fixing Letter Numbers</h2>";

// Show before
echo "<h3>Before Update:</h3>";
$result = $conn->query("SELECT id, letter_number, subject, department_id FROM Letter WHERE id IN (20, 22)");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Letter Number</th><th>Subject</th><th>Dept ID</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['letter_number']) . "</td>";
    echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
    echo "<td>" . htmlspecialchars($row['department_id']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Fix letter 20
$sql = "UPDATE Letter l
        JOIN Department d ON l.department_id = d.id
        SET l.letter_number = CONCAT(d.abbreviation, '-', DATE_FORMAT(l.date_received, '%y%m'), '-', l.id)
        WHERE l.id = 20";
if ($conn->query($sql)) {
    echo "<p>✓ Letter 20 updated</p>";
} else {
    echo "<p>Error updating letter 20: " . $conn->error . "</p>";
}

// Fix letter 22
$sql = "UPDATE Letter l
        JOIN Department d ON l.department_id = d.id
        SET l.letter_number = CONCAT(d.abbreviation, '-', DATE_FORMAT(l.date_received, '%y%m'), '-', l.id)
        WHERE l.id = 22";
if ($conn->query($sql)) {
    echo "<p>✓ Letter 22 updated</p>";
} else {
    echo "<p>Error updating letter 22: " . $conn->error . "</p>";
}

// Fix all letters with NULL or empty letter_number
$sql = "UPDATE Letter l
        JOIN Department d ON l.department_id = d.id
        SET l.letter_number = CONCAT(d.abbreviation, '-', DATE_FORMAT(l.date_received, '%y%m'), '-', l.id)
        WHERE l.letter_number IS NULL 
           OR l.letter_number = '' 
           OR l.letter_number = '0'";
$result = $conn->query($sql);
if ($result) {
    echo "<p>✓ Fixed " . $conn->affected_rows . " letters with invalid letter numbers</p>";
} else {
    echo "<p>Error fixing letters: " . $conn->error . "</p>";
}

// Show after
echo "<h3>After Update:</h3>";
$result = $conn->query("SELECT id, letter_number, subject, department_id FROM Letter WHERE id IN (20, 22)");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Letter Number</th><th>Subject</th><th>Dept ID</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['letter_number']) . "</td>";
    echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
    echo "<td>" . htmlspecialchars($row['department_id']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><p><strong>All letter numbers have been fixed!</strong></p>";
echo "<p><a href='src/letters.php'>Go to Letters Page</a></p>";
?>
