<?php

include "_bootstrap.php";

// Grab all the leaf urls
$leafs = $conn->query("SELECT id, url FROM leaf_url WHERE active = 1 AND NOW() > DATE_ADD(time_last_scraped, INTERVAL 1 DAY)");

// Retrieve all the leaf content
foreach ($leafs as $leaf) {
    echo "retrieving " . $leaf['url'] . "\n";

    $html = false;
    $i = 0;

    while ($html === false && $i < 5) {
        $html = @file_get_contents($leaf['url']);
        $i++;
    }

    // Save html to database
    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("
            INSERT INTO scraped_html (leaf_id, html, date_first_retrieved, date_last_checked, hash)
            VALUES (?,  ?, NOW(), NOW(), ?)
            ON DUPLICATE KEY UPDATE date_last_checked = NOW()
        ");
        $stmt->bindValue(1, $leaf['id']);
        $stmt->bindValue(2, $html);
        $stmt->bindValue(3, md5($leaf['id']));
        $stmt->execute();

        // Update leaf url meta data
        $data = $conn->executeQuery("SELECT date_first_retrieved > DATE_SUB(date_last_checked, INTERVAL 2 MONTH) as active FROM scraped_html WHERE id = ?", [$conn->lastInsertId()])->fetch();
        $conn->update('leaf_url', ['active' => $data['active'], 'time_last_scraped' => (new DateTime())->format('Y-m-d H:i:s')], ['id' => $leaf['id']]);
        $conn->commit();
    } catch(Exception $e) {
        // Rollback & error out, something went wrong and we have to fix this
        $conn->rollback();
        throw $e;
    }

}
