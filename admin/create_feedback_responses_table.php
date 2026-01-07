<?php
require_once 'config.php';

// SQL to create feedback_responses table
$sql = "CREATE TABLE IF NOT EXISTS `feedback_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `feedback_responses_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `feedback_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_responses_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

try {
    // Execute the SQL
    if ($conn->query($sql)) {
        echo "Table 'feedback_responses' created successfully!";
    } else {
        echo "Error creating table: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Close the connection
$conn->close();
?> 