<?php

use Sunra\PhpSimple\HtmlDomParser;

include "_bootstrap.php";

// Get all HTML data
$pages = $conn->query("
    SELECT s.id, s.html, l.url
    FROM scraped_html as s
    LEFT JOIN leaf_url as l ON (l.id = s.leaf_id)
    WHERE normalized = 0
");

$skipped = 0;

foreach ($pages as $page) {

    echo "processing scraped page with id " . $page['id'] . "\n";

    $dom = HtmlDomParser::str_get_html($page['html']);
    $labelsDOM = $dom->find("#content .blog .entry");

    // Process url data
    preg_match("/^((http[s]?):\/)?\/?([^:\/\s]+)((\/\w+)*\/)([\w\-\.]+[^#?\s]+)(.*)?(#[\w\-]+)?$/", $page['url'], $data);

    $domain = "";
    $month = 0;
    $year = 0;
    $day = 0;
    $type = 0;

    if (count($data) != 8) {
        echo "skipping, unknown amount of url parameters\n";
        $skipped++;
        continue;
    } else {
        $domain = $data[3];
        $month = preg_replace("/[^0-9]/", '', $data[6]);
        $year = preg_replace("/[^0-9]/", '', $data[5]);
    }

    // Determine host
    if ($domain == 'ranobe-mori.net') {
        $type = 0;
    }
    if ($domain == 'bl.ranobe-mori.net') {
        $type = 1;
    }
    if ($domain == 'jp.ranobe-mori.net') {
        $type = 2;
    }

    // Process html structure
    foreach ($labelsDOM as $labelDOM) {
        $label = $labelDOM->find('h2')[0];
        $rows = $labelDOM->find('tr');
        $dates = $labelDOM->find('div.entry-content');

        if (!is_object($label)) {
            continue;
        }

        foreach ($dates as $dateRow) {
            $pos = $pos = strpos($dateRow->text(), "発売");
            if ($pos !== false) {
                 $day = substr($dateRow->text(), 0, $pos);
            }
        }

        $labelID = 0;

        // Skip header rows & unknown length rows
        foreach (array_slice($rows, 1) as $row) {

            $cnt = count($row->children());
            if ($cnt != 7 && $cnt != 6 && $cnt != 5) {
                if ($cnt != 1) {
                    echo "skipped (row length " . $cnt . ")\n";
                    $skipped++;
                }
                continue;
            }

            if ($cnt == 7) {
                $labelID = getLabelIdByName($row->children(1)->text(), $conn);
            } elseif (($cnt == 6 || $cnt == 5) && $labelID == 0) {
                $labelID = getLabelIdByName($label->text(), $conn);
            }

            $a = $row->find('a');
            $url = (count($a) > 0) ? $a[0]->href : null;

            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("
                    INSERT INTO book (label_id, title, author, artist, isbn, price, release_date, url, `type`)
                    VALUES (:label_id,  :title, :author, :artist, :isbn, :price, :rls, :url, :type)
                    ON DUPLICATE KEY UPDATE
                    price = :price,
                    release_date = :rls,
                    url = :url
                ");
                $date = new DateTime();

                $stmt->bindParam(":label_id", $labelID);

                if ($cnt == 6) {
                    $stmt->bindParam(":title", $row->children(1)->text());
                    $stmt->bindParam(":author", $row->children(2)->text());
                    $stmt->bindParam(":artist", $row->children(3)->text());
                    $stmt->bindParam(":price", $row->children(4)->text());
                    $stmt->bindParam(":isbn", $row->children(5)->text());
                    $day = $row->children(0)->text();
                } elseif ($cnt == 7) {
                    $stmt->bindParam(":title", $row->children(2)->text());
                    $stmt->bindParam(":author", $row->children(3)->text());
                    $stmt->bindParam(":artist", $row->children(4)->text());
                    $stmt->bindParam(":price", $row->children(5)->text());
                    $stmt->bindParam(":isbn", $row->children(6)->text());
                    $day = $row->children(0)->text();
                } elseif ($cnt == 5) {
                    $stmt->bindParam(":title", $row->children(0)->text());
                    $stmt->bindParam(":author", $row->children(1)->text());
                    $stmt->bindParam(":artist", $row->children(2)->text());
                    $stmt->bindParam(":price", $row->children(3)->text());
                    $stmt->bindParam(":isbn", $row->children(4)->text());
                } else {
                    echo "Skipping\n";
                    $skipped++;
                    continue;
                }


                $exp = explode('/', preg_replace("/[^0-9\/]/", '', $day));
                if (count($exp) > 1) {
                    $day = $exp[1];
                    $month = $exp[0];
                }

                $date->setDate((int)$year, (int)$month, (int)$day);
                $stmt->bindParam(":url", $url);
                $stmt->bindParam(":rls", $date->format('Y-m-d'));
                $stmt->bindParam(":type", $type);
                $stmt->execute();

                $stmt = $conn->update('scraped_html', ['normalized' => 1], ['id' => $page['id']]);
                $conn->commit();
            } catch(Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }
    }

    $day = 0;
}


function getLabelIdByName($name, $conn)
{
    $stmt = $conn->executeQuery("SELECT id FROM label WHERE `name` = ?", [$name]);

    $labelID = $stmt->fetch();

    if ($labelID == false) {
        $conn->insert('label', ['name' => $name]);
        return $conn->lastInsertId();
    } else {
        return $labelID['id'];
    }
}
