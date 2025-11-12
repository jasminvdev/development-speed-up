    <?php
$host = 'localhost';
$dbname = 'magento';
$user = 'invigorate';
$pass = 'Invi@123';
$doCmsPage = true;
$doCmsBlock = true;
$cms_page = 'cms_page';
$cms_block = 'cms_block';

function pr($object = "Comes here", $exit = 1) {
    echo "<pre>";
    print_r($object);
    echo "</pre>";
    if ($exit) {
        exit();
    }
}

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
function extractDivContentDynamic($html, &$divAttributes = '') {
    $pattern = '/<div([^>]*data-content-type="html"[^>]*)>(.*?)<\/div>/s';

    if (preg_match($pattern, $html, $matches)) {
        $divAttributes = trim($matches[1]); // Capture div attributes
        return $matches[2]; // Return inner HTML if div exists
    } else {
        return $html; // Return full HTML if div not found
    }
}


function addDivContentDynamic($innerHtml, $divAttributes) {
    return "<div $divAttributes>$innerHtml</div>";
}

function wrapTextWithTrans($node) {
    if ($node->nodeType === XML_TEXT_NODE) {
        $text = trim($node->nodeValue);
        if (!empty($text) && (strpos($text, '{{trans') === false) && (strpos($text, 'SRC_PLACEHOLDER_CUSTOM_') === false)) {
            $parentTag = $node->parentNode->nodeName;
            // Ensure <h2> tags are not modified incorrectly
            if ($parentTag !== 'style' && $parentTag !== 'script') {

                $node->nodeValue = '{{trans "' . htmlspecialchars($text, ENT_QUOTES) . '"}}';
                // $escapedText = addslashes($text); 
                // $escapedText = htmlspecialchars($text, ENT_QUOTES);

                // $content = str_replace('"', "&quot;", $text); 
                // $node->nodeValue = '{{trans "%value" value="' . $text . '"}}';
            }
        }
    }
}


function processHtmlContent($html) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); 
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//text()[not(ancestor::style) and not(ancestor::script)]') as $textNode) {
        wrapTextWithTrans($textNode);
    }

    foreach ($xpath->query('//*[@title or @alt]') as $node) {
        if ($node->hasAttribute('title')) {
            $title = trim($node->getAttribute('title'));
            if (!empty($title) && (strpos($title, '{{trans') === false) && (strpos($title, 'SRC_PLACEHOLDER_CUSTOM_') === false)) {
                $node->setAttribute('title', '{{trans "' . htmlspecialchars($title, ENT_QUOTES) . '"}}');
            }
        }
        if ($node->hasAttribute('alt')) {
            $alt = trim($node->getAttribute('alt'));
            if (!empty($alt) && (strpos($alt, '{{trans') === false) && (strpos($alt, 'SRC_PLACEHOLDER_CUSTOM_') === false)) {
                $node->setAttribute('alt', '{{trans "' . htmlspecialchars($alt, ENT_QUOTES) . '"}}');
            }
        }
    }
    return $dom->saveHTML();
}
if($doCmsPage){    
    $sql = "SELECT page_id, content FROM $cms_page WHERE page_id = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pageId = $row['page_id'];
            $divAttributes = '';
            $removeDiv = extractDivContentDynamic($row['content'], $divAttributes);
            $content = htmlspecialchars_decode($removeDiv); 
            
            preg_match_all('/{{[^{}]+}}/', $content, $matches);

            $srcMap = [];
            foreach ($matches[0] as $index => $match) {
                // pr($match);
                $placeholder = "SRC_PLACEHOLDER_CUSTOM_{$index}_TEST";
                $srcMap[$placeholder] = $match; 
                $content = str_replace($match, $placeholder, $content); 
            }
            

            $updatedContent = processHtmlContent($content);

            if (empty(trim($content))) {
                echo "Skipping empty content for block_id: $pageId\n";
                continue;
            }

            if ($updatedContent === $content) {
                echo "No changes for block_id: $pageId\n";
                continue;
            }
                
            foreach ($srcMap as $placeholder => $original) {
                $updatedContent = str_replace($placeholder, $original, $updatedContent);
            }

            // pr($updatedContent,0);
            $pattern = '/<div([^>]*data-content-type="html"[^>]*)>(.*?)<\/div>/s';

            if (preg_match($pattern, $row['content'], $matches)) {
                $updatedContent = addDivContentDynamic(htmlspecialchars($updatedContent,ENT_HTML5), $divAttributes);
            }

            // pr($updatedContent);
            $stmt = $conn->prepare("UPDATE $cms_page SET content = ? WHERE page_id = ?");
            $stmt->bind_param("si", $updatedContent, $pageId);

            if ($stmt->execute()) {
                echo "Updated block_id: $pageId successfully\n";
            } else {
                echo "Error updating block_id: $pageId - " . $conn->error . "\n";
            }

            $stmt->close();
        }
    } else {
        echo "0 results";
    }
}
if($doCmsBlock){

}

$conn->close();
?>
