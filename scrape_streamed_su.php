<?php

set_time_limit(500);

function discoverListings() {
    $maxRetries = 3;
    $attempt = 0;
    $success = false;

    while ($attempt < $maxRetries && !$success) {
        $attempt++;
        $html = file_get_contents('https://fetch.data-search.workers.dev/?url=https://streamed.su/');
        
        if ($html !== false) {
            $success = true;
        } else {
            echo "Attempt $attempt failed.\n";
        }

        if (!$success && $attempt < $maxRetries) {
            sleep(30); // Wait 30 seconds before retrying
        }
    }

    if (!$success) {
        echo "Failed after $maxRetries attempts.\n";
        exit;
    }

    $jsonRegex = '/(?<=const data = ).*?\](?=;)/s';
    preg_match($jsonRegex, $html, $matches);

    if (isset($matches[0])) {
        $jsonString = $matches[0];
        $jsonString = str_replace('void 0', 'null', $jsonString);
        $jsonString = fix_json($jsonString);
        $jsonData = json_decode($jsonString, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($jsonData[1]['data']['liveMatches'])) {
                $allMatches = $jsonData[1]['data']['allMatches'];
                $items = [];

                foreach ($allMatches as $match) {
                   
                    $poster = 'https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/images/sports.png';
					
                    if (stripos($match['category'], 'afl') === 0) {
                        $poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/afl.png?raw=true';
                    }
					if (stripos($match['category'], 'american-football') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/am-football.png?raw=true';
					}
					if (stripos($match['category'], 'baseball') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/baseball.png?raw=true';
					}
					if (stripos($match['category'], 'basketball') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/basketball.png?raw=true';
					}
					if (stripos($match['category'], 'billiards') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/billiards.png?raw=true';
					}
					if (stripos($match['category'], 'cricket') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/cricket.png?raw=true';
					}	
					if (stripos($match['category'], 'darts') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/darts.PNG?raw=true';
					}	
					if (stripos($match['category'], 'football') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/football.png?raw=true';
					}					
					if (stripos($match['category'], 'fight') === 0) {
						$poster = 'https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/images/fighting.png';
					}	
					if (stripos($match['category'], 'golf') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/golf.png?raw=true';
					}	
					if (stripos($match['category'], 'hockey') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/hockey.png?raw=true';
					}	
					if (stripos($match['category'], 'motor-sports') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/motor.png?raw=true';
					}	
					if (stripos($match['category'], 'nba') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/nba.png?raw=true';
					}	
					if (stripos($match['category'], 'rugby') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/rugby.png?raw=true';
					}		
					if (stripos($match['category'], 'tennis') === 0) {
						$poster = 'https://github.com/dtankdempse/streamed-su-sports/blob/main/images/tennis.png?raw=true';
					}						
					if (isset($match['source'], $match['date'], $match['id'], $match['title'], $match['category'])) {					
                        $timestamp = $match['date'] / 1000;
                        $date = new DateTime();
                        $date->setTimestamp($timestamp);
                        $date->setTimezone(new DateTimeZone('America/New_York'));
                        $formattedDate = $date->format('h:i A T - (m/d/Y)');
                        $streamUrl = "https://rr.vipstreams.in/" . $match['source'] . "/js/" . $match['id'] . "/1/playlist.m3u8";
                        $epgId = md5($match['id'] . $match['date']);  // EPG ID

                        $items[] = [
                            'id' => $match['id'],
                            'date' => $formattedDate,
                            'time' => $match['date'],
                            'title' => $match['title'],
                            'posterImage' => $poster,
                            'url' => "https://streamed.su/watch/" . $match['id'],
                            'stream' => $streamUrl,
                            'Referer' => 'https://embedme.top/',
                            'type' => ucwords(strtolower($match['category'])),
                            'epg' => $epgId
                        ];
                    }
                }
                return $items;
            } else {
                return ["error" => "No live matches found in the JSON data."];
            }
        } else {
            return ["error" => "Failed to decode the JSON data. Error: " . json_last_error_msg()];
        }
    } else {
        return ["error" => "Could not find embedded JSON in the HTML."];
    }
}


function generateM3U8($items) {
    $m3u8 = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {        
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $m3u8 .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $m3u8 .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $m3u8 .= $item['stream'] . "\n";
    }
    file_put_contents('playlist.m3u8', $m3u8);
}

function generateProxyM3U8($items) {
    $m3u8 = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {        
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $m3u8 .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $m3u8 .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $m3u8 .= "https://m3u8.justchill.workers.dev?url=" . urlencode($item['stream']) . "&referer=" . $item['Referer'] . "\n";
    }
    file_put_contents('proxied_playlist.m3u8', $m3u8);
}

function generateTivimateM3U8($items) {
    $m3u8 = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {        
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $m3u8 .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $m3u8 .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $m3u8 .= $item['stream'] . "|Referer=" . $item['Referer'] . "\n";
    }
    file_put_contents('tivimate_playlist.m3u8', $m3u8);
}

function generateVLC($items) {
	$vlc = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $vlc .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $vlc .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $vlc .= "#EXTVLCOPT:http-referrer=" . $item['Referer'] . "\n";
        $vlc .= $item['stream'] . "\n";
    }
    file_put_contents('vlc_playlist.m3u8', $vlc);
}

function generateKODIPOP($items) {
	$kodipop = "#EXTM3U url-tvg=\"https://raw.githubusercontent.com/dtankdempse/streamed-su-sports/main/epg.xml\"\n";
    foreach ($items as $item) {
        $date = new DateTime("@".($item['time'] / 1000));
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $formattedTime = $date->format('h:i A -');

        $kodipop .= "#EXTINF:-1 tvg-id=\"" . $item['epg'] . "\" tvg-name=\"" . $item['title'] . "\" tvg-logo=\"" . $item['posterImage'] . "\" group-title=\"" . $item['type'] . "\",";
        $kodipop .= $formattedTime . " " . $item['title'] . " - " . $item['date'] . "\n";
        $kodipop .= "#KODIPROP:inputstream.adaptive.stream_headers=Referer=" . urlencode($item['Referer']) . "\n";
        $kodipop .= $item['stream'] . "\n";
    }
    file_put_contents('kodi_playlist.m3u8', $kodipop);
}

function generateEPG($items) {
    $epg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $epg .= '<tv>' . "\n";

    foreach ($items as $item) {
        $epg .= '  <channel id="' . $item['epg'] . '">' . "\n";
        $epg .= '    <display-name>' . htmlspecialchars($item['title'] . ' - ' . $item['date']) . '</display-name>' . "\n";
        $epg .= '    <icon src="' . htmlspecialchars($item['posterImage']) . '" />' . "\n";
        $epg .= '  </channel>' . "\n";
    }
	
	$currentTime = time() - 3600;

    foreach ($items as $item) {          
        $startTime = date('YmdHis', $currentTime) . ' +0000';
        $endTime = date('YmdHis', $currentTime + (48 * 3600)) . ' +0000';

        $date = new DateTime();
        $date->setTimestamp($item['time'] / 1000);

        $date->setTimezone(new DateTimeZone('America/Los_Angeles'));
        $ptTime = $date->format('h:i A T');

        $date->setTimezone(new DateTimeZone('America/Denver'));
        $mtTime = $date->format('h:i A T');

        $date->setTimezone(new DateTimeZone('America/New_York'));
        $etTime = $date->format('h:i A T');

        $formattedDate = $date->format('m/d/Y');
        $description = "$ptTime / $mtTime / $etTime - ($formattedDate)";

        $epg .= '  <programme start="' . $startTime . '" stop="' . $endTime . '" channel="' . $item['epg'] . '">' . "\n";
        $epg .= '    <title>' . htmlspecialchars($item['title'] . ' - ' . $item['date']) . '</title>' . "\n";
        $epg .= '    <desc>' . htmlspecialchars($description) . '</desc>' . "\n";
        $epg .= '  </programme>' . "\n";
    }

    $epg .= '</tv>';

    file_put_contents('epg.xml', $epg);
}



function fix_json($j){
  $j = trim( $j );
  $j = ltrim( $j, '(' );
  $j = rtrim( $j, ')' );
  $a = preg_split('#(?<!\\\\)\"#', $j );
  for( $i=0; $i < count( $a ); $i+=2 ){
    $s = $a[$i];
    $s = preg_replace('#([^\s\[\]\{\}\:\,]+):#', '"\1":', $s );
    $a[$i] = $s;
  }
  $j = implode( '"', $a );
  return $j;
}

function saveItemsToJson($items) {
    $jsonData = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonData === false) {
        echo "JSON encode error: " . json_last_error_msg();
        return;
    }
    $result = file_put_contents('streamed_su.json', $jsonData);
    if ($result === false) {
        echo "Failed to write to file.";
        exit;
    }
}

// Filter out events that have passed by more than 4 hours
// Sort the remaining events by time (ascending, so soonest events first)
function filterAndSortEvents($items) {
    $currentTime = time();
    $fourHoursAgo = $currentTime - (4 * 3600); // 4 hours ago in seconds
   
    $upcomingEvents = array_filter($items, function ($item) use ($fourHoursAgo) {
        return ($item['time'] / 1000) >= $fourHoursAgo;
    });

    usort($upcomingEvents, function ($a, $b) {
        return ($a['time'] - $b['time']);
    });

    return $upcomingEvents;
}


header('Content-Type: application/json');
$items = discoverListings();
if (isset($items['error']) || empty($items)) {
    echo json_encode($items); 
    exit(1);
}
$filteredSortedItems = filterAndSortEvents($items);
generateM3U8($filteredSortedItems);
generateTivimateM3U8($filteredSortedItems);
generateVLC($filteredSortedItems);
generateProxyM3U8($filteredSortedItems);
generateKODIPOP($filteredSortedItems);
generateEPG($filteredSortedItems);
saveItemsToJson($filteredSortedItems);
echo json_encode($filteredSortedItems);

?>
