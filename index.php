<?php
header('Content-type: text/xml');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

header('Content-type: text/xml');

$feedName = "My Audio Feed";
$feedDesc = "Feed for the my audio files in some server folder";
$feedURL = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/";
$feedBaseURL = $feedURL;
$allowed_ext = ".mp4,.MP4,.mp3,.MP3";

include("../getid3/getid3.php");
$getID3 = new getID3;

?><<?= '?'; ?>xml version="1.0" encoding="UTF-8" <?= '?'; ?>>
    <rss xmlns:atom="http://www.w3.org/2005/Atom"
        xmlns:media="http://search.yahoo.com/mrss/"
        xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
        version="2.0">
        <channel>
            <title><?= $feedName ?></title>
            <link><?= $feedURL ?></link>
            <description><?= $feedDesc ?></description>
            <atom:link href="<?= $feedURL ?>feed.xml" rel="self" type="application/rss+xml" />

            <?php
            $files = [];
            $dir = opendir("./");

            while (($file = readdir($dir)) !== false) {
                $path_info = pathinfo($file);
                $ext = strtoupper($path_info['extension']);

                if ($file !== '.' && $file !== '..' && !is_dir($file) && strpos($allowed_ext, $ext) > 0) {
                    $files[] = [
                        'name' => $file,
                        'timestamp' => filectime($file)
                    ];
                }
            }
            closedir($dir);

            // Sort files by timestamp (neueste zuerst)
            usort($files, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

            // Letzte Aktualisierung setzen
            if (!empty($files)) {
                echo "<lastBuildDate>" . date(DATE_RSS, $files[0]['timestamp']) . "</lastBuildDate>\n";
            }

            // Feed-Einträge generieren
            foreach ($files as $file) {
                if (!empty($file['name'])) {
                    $filek = $getID3->analyze($file['name']);
                    $playtime_seconds = $filek['playtime_seconds'] ?? 0;

                    // Cover-Bild extrahieren, falls vorhanden
                    $coverImageBase64 = "";
                    if (isset($filek['comments']['picture'][0])) {
                        $imageData = $filek['comments']['picture'][0]['data'];
                        $imageMime = $filek['comments']['picture'][0]['image_mime'];
                        $coverImageBase64 = "data:$imageMime;base64," . base64_encode($imageData);
                    }

                    echo "<item>\n";
                    echo "<title>" . htmlspecialchars($file['name']) . "</title>\n";
                    echo "<enclosure url='" . $feedBaseURL . htmlspecialchars($file['name']) . "' length='" . $playtime_seconds . "' type='audio/mpeg' />\n";
                    echo "<link>" . $feedBaseURL . htmlspecialchars($file['name']) . "</link>\n";
                    echo "<guid>" . $feedBaseURL . htmlspecialchars($file['name']) . "</guid>\n";
                    echo "<pubDate>" . date(DATE_RSS, $file['timestamp']) . "</pubDate>\n";
                    echo "<itunes:duration>" . gmdate("H:i:s", $playtime_seconds) . "</itunes:duration>\n";

                    // Falls ein Cover existiert, es als <itunes:image> und <media:thumbnail> einfügen
                    if ($coverImageBase64) {
                        echo "<itunes:image href='" . htmlspecialchars($coverImageBase64) . "' />\n";
                        echo "<media:thumbnail url='" . htmlspecialchars($coverImageBase64) . "' />\n";
                    }

                    echo "</item>\n";
                }
            }
            ?>
        </channel>
    </rss>