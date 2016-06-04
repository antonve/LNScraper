<?php

use Sunra\PhpSimple\HtmlDomParser;

include "_bootstrap.php";

$urls = [
    "http://ranobe-mori.net/",
    "http://bl.ranobe-mori.net/",
    "http://jp.ranobe-mori.net/"
];

foreach ($urls as $url) {
    echo "retrieving " . $url . "\n";

    $html = file_get_contents($url);
    $dom = HtmlDomParser::str_get_html($html);
    $links = $dom->find("#monthly-archives-block a");

    foreach ($links as $link) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO leaf_url (url, time_discovered, active)
            VALUES (?,  NOW(), 1)
        ");
        $stmt->bindValue(1, $link->href);
        $stmt->execute();
    }
}
