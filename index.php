<?php
header('Content-type: text/xml');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Set the content type as XML
header('Content-type: text/xml');

$feedName = "My Audio Feed";
$feedDesc = "Feed for the my audio files in some server folder";
$feedURL = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/";
$feedBaseURL = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/"; // must end in trailing forward slash (/).
$allowed_ext = ".mp4,.MP4,.mp3,.MP3";

include("../getid3/getid3.php");
$getID3 = new getID3;

?><<?= '?'; ?>xml version="1.0" <?= '?'; ?>>
    <rss xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
        <channel>
            <title><?= $feedName ?></title>
            <link><?= $feedURL ?></link>
            <description><?= $feedDesc ?></description>
            <atom:link href="http://gogglesoptional.com/bloopers" rel="self" type="application/rss+xml" />

            <?php
            $files = array();
            $dir = opendir("./");

            while (($file = readdir($dir)) !== false) {
                $path_info = pathinfo($file);
                $ext = strtoupper($path_info['extension']);

                if ($file !== '.' && $file !== '..' && !is_dir($file) && strpos($allowed_ext, $ext) > 0) {
                    $files[] = array('name' => $file, 'timestamp' => filectime($file), 'duration' => filesize($file));
                }
            }
            closedir($dir);

            // Sort files by timestamp (newest first)
            usort($files, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });

            // Get the most recent update date
            $lastUpdated = !empty($files) ? date(DATE_RSS, $files[0]['timestamp']) : null;

            // Add the last update date to the feed
            if ($lastUpdated) {
                echo "<lastBuildDate>{$lastUpdated}</lastBuildDate>\n";
            }

            // Build feed content
            foreach ($files as $file) {
                if (!empty($file['name'])) {
                    $filek = $getID3->analyze($file['name']);
                    $playtime_seconds = $filek['playtime_seconds'] ?? 0;

                    echo "<item>\n";
                    echo "<title>" . htmlspecialchars($file['name']) . "</title>\n";
                    echo "<enclosure url='" . $feedBaseURL . htmlspecialchars($file['name']) . "' length='" . $playtime_seconds . "' type='audio/mpeg' />\n";
                    echo "<link>" . $feedBaseURL . htmlspecialchars($file['name']) . "</link>\n";
                    echo "<guid>" . $feedBaseURL . htmlspecialchars($file['name']) . "</guid>\n";
                    echo "<pubDate>" . date(DATE_RSS, $file['timestamp']) . "</pubDate>\n";
                    echo "<itunes:duration>" . gmdate("H:i:s", $playtime_seconds) . "</itunes:duration>\n";
                    echo "</item>\n";
                }
            }
            ?>
        </channel>
    </rss>