<?php
$host = 'localhost';
$dbname = 'dbname';
$user = 'username';
$pass = 'password';
$getCmsPage = true;
$getCmsBlock = true;
$cms_page = 'cms_page';
$cms_block = 'cms_block';
$translatableStrings = [];

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if($getCmsPage){
    $sql = "SELECT content FROM $cms_page";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $content = htmlspecialchars_decode($row['content']);

            preg_match_all("/{{trans ['\"]([^'\"]+)['\"]}}/", $content, $transMatches);
            if (!empty($transMatches[1])) {
                foreach ($transMatches[1] as $transText) {
                    $translatableStrings[$transText] = true;
                }
            }
        }
    }
}

if($getCmsBlock){    
    $sqlblock = "SELECT content FROM $cms_block";
    $resultBlock = $conn->query($sqlblock);

    if ($resultBlock->num_rows > 0) {
        while ($row = $resultBlock->fetch_assoc()) {
            $contentblock = htmlspecialchars_decode($row['content']);

            preg_match_all("/{{trans ['\"]([^'\"]+)['\"]}}/", $contentblock, $transMatchesblock);
            if (!empty($transMatchesblock[1])) {
                foreach ($transMatchesblock[1] as $transText) {
                    $translatableStrings[$transText] = true;
                }
            }
        }
    }
}

$conn->close();

$filePath = 'cms_translated_line.txt';

file_put_contents($filePath, implode("\n", array_keys($translatableStrings)));

echo "Translation strings extracted and saved to $filePath";
?>
