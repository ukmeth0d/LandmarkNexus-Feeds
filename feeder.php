<?php
if(!isset($_GET['game'])){
    $_GET['game'] = 'everquestnext';
}
if(!isset($_GET['maxPosts'])){
    $_GET['maxPosts'] = 10;
}

$game = $_GET['game'];
$maxPosts = $_GET['maxPosts'];

$url = 'https://forums.daybreakgames.com/'. $game .'/index.php?recent-activity/';
switch ($game) {
    case 'everquestnext':
        $title = 'EverQuest Next Staff Post Tracker';
        break;
    case 'landmark':
        $title = 'Landmark Staff Post Tracker';
        break;
    case 'h1z1':
        $title = 'H1Z1 Staff Post Tracker';
        break;
    case 'eq':
        $title = 'EverQuest Staff Post Tracker';
        break;
    case 'eq2':
        $title = 'EverQuest II Staff Post Tracker';
        break;
    case 'dcuo':
        $title = 'DC Universe Online Staff Post Tracker';
        break;
    case 'ps2':
        $title = 'PlanetSide 2 Staff Post Tracker';
        break;
}
$description = 'Recent posts from Daybreak Employees';

$userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';

header('Content-type: text/xml; charset=utf-8', true);

echo '<?xml version="1.0" encoding="UTF-8"?'.'>' . PHP_EOL;
echo '<rss version="2.0">' . PHP_EOL;
echo '<channel>' . PHP_EOL;
echo '  <title>' . $title . '</title>' . PHP_EOL;
echo '  <link>' . $url . '</link>' . PHP_EOL;
echo '  <description>' . $description . '</description>' . PHP_EOL;

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
curl_setopt($curl, CURLOPT_AUTOREFERER, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt($curl, CURLOPT_TIMEOUT, 2 );

$html = curl_exec( $curl );

$html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'utf-8');

curl_close( $curl );

$dom = new DOMDocument();

@$dom->loadHTML($html);

$nodes = $dom->getElementsByTagName('*');

$currentCount = 0;

foreach($nodes as $node){

    if($node->nodeName == 'div' && $node->getAttribute('class') == 'content' && ($currentCount < $maxPosts)){


        echo '<item>' . PHP_EOL;

        getV3Data($node);

        echo '</item>' . PHP_EOL;

        $currentCount++;
    }
}

echo '</channel></rss>';

function getV3Data($node){
    $pNodes = $node->getElementsByTagName('p');
    $pTitle = '';
    $pDesc = '';
    foreach($pNodes as $pNode){
        if($pNode->getAttribute('class') == 'description') {
            $pTitle = $pNode->textContent;
        }
        if(strpos($pNode->getAttribute('class'), 'post') !== 'false'){
            $pDesc = $pNode->textContent;
        }
    }

    $h3Nodes = $node->getElementsByTagName('h3');
    foreach ($h3Nodes as $reply) {

        $inodes = $reply->childNodes;

        if($pTitle !== ''){
            echo '<title>' . trim(str_replace('posted a new thread.', 'posted:', $pTitle) . ' ' . $reply->textContent) . '</title>' . PHP_EOL;
            if(strpos($reply->getAttribute('class'), 'title') !== false){
                foreach($inodes as $inode) {
                    if($inode->nodeName == 'a'){
                        echo '<link>' . 'https://forums.daybreakgames.com/' . $game . '/' . $inode->getAttribute('href') . '</link>' . PHP_EOL;
                    }
                }
            }
        } else {
            echo '<title>' . trim(str_replace('replied to the thread', 'replied to:', $reply->textContent)) . '</title>' . PHP_EOL;
            foreach($inodes as $inode) {
                if($inode->nodeName == 'a' && $inode->getAttribute('class') == 'PreviewTooltip'){
                    echo '<link>' . 'https://forums.daybreakgames.com/' . $game . '/' . $inode->getAttribute('href') . '</link>' . PHP_EOL;
                }
            }
        }
        echo '<description>' . trim($pDesc) . '</description>' . PHP_EOL;
    }

    $dateNodes = $node->getElementsByTagName('span');
    foreach($dateNodes as $date){
        if($date->getAttribute('class') == 'DateTime'){
            echo '<pubDate>' . date('r', strtotime(str_replace(' at ',' ',$date->getAttribute('title')))) . '</pubDate>' . PHP_EOL;
        }
    }
    $dateNodes = $node->getElementsByTagName('abbr');
    foreach($dateNodes as $date){
        if($date->getAttribute('class') == 'DateTime'){
            echo '<pubDate>' . date('r', strtotime(str_replace(' at ',' ',$date->getAttribute('title')))) . '</pubDate>' . PHP_EOL;
        }
    }
}

?>