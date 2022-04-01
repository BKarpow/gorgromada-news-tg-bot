<?php

include (__DIR__ . '/vendor/autoload.php');
include (__DIR__ . '/config.php');

$apiJsonAirChannel = 'https://tg.i-c-a.su/json/air_alert_ua?limit=100';
$fileLastAirAlarmTimestamp = './airAlarmLast.txt';



/**
 * Створює запит GET типу за допомогою бібліотеки cURL
 * @param string $url
 * @return string
 */
function curlGetRequest(string $url, bool $show = false):string
{
    $agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36'; 
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response_data = curl_exec($ch);
    if (curl_errno($ch) > 0) {
        die('Пимилка curl: ' . curl_error($ch));
    }
    curl_close($ch);
    if ($show) {
        echo "<pre>";
        var_dump($response_data);
        echo "</pre>";
        die();
    }
    return $response_data;
}


/**
 * Получает данные из RSS и возражает строку xml
 * 
 * @param string $linkRss - адрес. источника rss
 * @return string - data xml.
 */
function getDataFromRss(string $linkRss):string
{
    return curlGetRequest($linkRss);
}

/**
 * Отфильтрует лишние символы из описания
 * 
 * @param string $description
 * @return string
 */
function stripHtmlCode(string $description):string
{
    $filterMask = [
        '&amp;' => '',
        'amp;' => '',
        '&laquo;' => '',
        'laquo;' => '',
        'ndash;' => '',
        '&ndash;' => '',
        '&raquo;' => '',
        'raquo;' => '',
        'nbsp;' => '',
        '&nbsp;' => ''
    ];
    $description = str_replace( array_keys($filterMask),
                              array_values($filterMask), $description);
    return $description;
}

/**
 * Вернет отформатировавший маслив из rss источника.
 * 
 * @param SimpleXMLElement $item
 * @return array 
 */
function getDataAsArray($item):array
{
    return [
        'title' => (string)$item->title,
        'pubDate' => @strtotime((string)$item->pubDate),
        'date' => date('d-m-Y H:i', @strtotime((string)$item->pubDate)),
        'description' => stripHtmlCode( (string)$item->description ),
        'link' => (string) $item->link,
    ];
}

/**
 * Вернет маслив новостей из источника rss.
 * 
 * @return array
 */
function parseRssDate():array
{
    global $linkRssChanel;
    $rssData = new SimpleXMLElement( getDataFromRss($linkRssChanel) );
    $rssArray = [ 'rss' => [] ];
    if ($rssData->channel->item) {
        foreach($rssData->channel->item as $item) {
            $rssArray['rss'][] = getDataAsArray($item);
        }
    }
    return $rssArray;
}

/**
 * Получает данные из файловой базы данных.
 * 
 * @return array
 */
function getDataFromJsonDb():array
{
    if (!file_exists(FILE_JSON_DB)){
        file_put_contents(FILE_JSON_DB, '{"rss":[]}');
    }
    return json_decode( file_get_contents( FILE_JSON_DB), true);
}

/**
 * Запишет данные в файловою базу.
 * 
 * @param array $data
 */
function writeDataInJsonDb(array $data)
{
    file_put_contents(FILE_JSON_DB, json_encode($data) );
}

/**
 * Проверяет новость на уникальность, записывает уникальные в базу.
 * Вернет true если новость уникальна или false если такая новость уже была.
 * @param array $news - маслив одной новости
 * @return bool
 */
function isUniqueNews(array $news):bool
{
    $d =  getDataFromJsonDb();
    $u = true;
    foreach($d['rss'] as $itemDb){
        if ($news['link'] === $itemDb['link']){
            $u = false;
            break;
        }
    }
    if ($u){
        $d['rss'][] = $news;
        writeDataInJsonDb($d);
    }
    return $u;
}

/**
 * Вернет массив уникальных новостей.
 * 
 * @return array
 */
function getUniqueNews():array
{
    $rssArray = parseRssDate();
    $gorNews = parseUkrNetForGor();
    $uNews = [];
    if (!empty($rssArray['rss'])){
        foreach($rssArray['rss'] as $news) {
            if ( isUniqueNews($news) ) {
                $uNews[] = $news;
            }
        }
    }
    $news = null;
    if (!empty($gorNews)){
        foreach($gorNews as $news) {
            if ( isUniqueNews($news) ) {
                $uNews[] = $news;
            }
        }
    }
    return $uNews;
}

/**
 * Вернет json оддачу в виде массива из ukr.net для Черкаськой области.
 * 
 * @return array
 */
function getArrayUkrNetCherkasyNews():array
{
    // $linkJsonSource = 'https://www.ukr.net/news/dat/cherkasy/2/';
    // return json_decode(curlGetRequest($linkJsonSource, true), true  );
    return [];
}

/**
 * Отфильтрует новости в которых в заголовке есть упоминания о Городише.
 * 
 * @param array $data - массив из отдачи Ukr.net
 * @param array - отфильтрованные новости
 * 
 */
function filterForGorodischeFromCherkasyNews(array $data):array
{
    $newses = [];
    if (!empty($data['tops']) ) {
        foreach($data['tops'] as $news) {
            if (preg_match('#ородищ#usi', $news['Title'])) {
                $newses[] = $news;
            }
        }
    }
    return $newses;
}

/**
 * Оформляет массив из новостей отдачи укр.нет согласно стандарту файловой базы
 * 
 * @param array $newses
 * @return array
 */
function formatterNewsForUrkNet(array $newses):array
{
    $nn = [];
    $n = (isset($newses['tops'])) ? $newses['tops'] : $newses;
    foreach($n as $news) {
        if (!empty($news)) {
            $nn[] = [
                'title' => $news['Title'],
                'pubDate' => $news['DateCreated'],
                'date' => date('d-m-Y H:i', $news['DateCreated']),
                'description' => $news['Description'],
                'link' => $news['Url']
            ];
            
        }
    }
    return $nn;

}

/**
 * Функция парсер для новостей из укр.нет
 * 
 * @return array - массив новостей.
 */
function parseUkrNetForGor():array
{
    $news = getArrayUkrNetCherkasyNews();
    $gorMews = filterForGorodischeFromCherkasyNews($news);
    $data =  formatterNewsForUrkNet($gorMews);
    return $data;
}


/**
 * Отправляет сообщения в Telegram канал
 * 
 * @return void
 */
function senderToTelegram()
{
    $newses = getUniqueNews();
    $telegram = new Telegram(TELEGRAM_TOKEN);
    $con = array('chat_id' => TELEGRAM_CHAT_ID, 'text' => '');
    foreach($newses as $news) {
        // echo "<pre>" . $news['title'] . PHP_EOL;
        $text = $news['date'] .
        PHP_EOL . $news['title'] . 
        PHP_EOL . $news['description'] . 
        PHP_EOL . $news['link'];
        $con['text'] = $text;
        $telegram->sendMessage($con);
    }
}

/**
 * Повертає часову мітку в форматі
 * @return string
 */
function getTimestamp():string
{
    $timestamp = date('(d.m/H:i)');
    return (string) $timestamp;
}

/**
 * Повітряна тривога, відправляє в канал повідомлення про початок та кінець тривоги
 * @param bool $start
 * @param null|string $dateString
 * @return void
 */
function airAlarm(bool $start = true, $dateString = null)
{
    $ts = (!empty($dateString)) ? $dateString : getTimestamp();
    $telegram = new Telegram(TELEGRAM_TOKEN);
    $con = array('chat_id' => TELEGRAM_CHAT_ID, 'text' => '');
    if ($start) {
        $con['text'] = '❗️❗️ПОВІТРЯНА ТРИВОГА ' . $ts;
    } else {
        $con['text'] = '🟢 ВІДБІЙ ПОВІТРЯНОЇ ТРИВОГИ ' . $ts;
    }
    $telegram->sendMessage($con);
}

/**
 * Функція яка отримує всі пости із каналу повітряних тривог та
 * повертає їх як відформатований масив
 * @param string $apiJsonUrl
 * @return array
 */
function getTelegramChannelPosts(string $apiJsonUrl):array
{
    $dataJson = curlGetRequest($apiJsonUrl);
    if (!empty($dataJson)) {
        $data = json_decode($dataJson, true);
        $rArray = [];
        foreach($data['messages'] as $message) {
            $r = [
                'dateRaw' => (int)$message['date'],
                'date' => date( '(d.m/H:i)', (int)$message['date']),
                'message' => strip_tags( $message['message'] ),
            ];
            if (preg_match('#Повітряна тривога#siu', $message['message'])) {
                $r['start'] = true;
            }
            if (preg_match('#Відбій тривоги#siu', $message['message'])) {
                $r['start'] = false;
            }
            $rArray[] = $r;
        }
        return $rArray;
    }
    return [];
}


/**
 * Фільрує тривоги тільки по регіону області
 * @param string $region
 * @param array $posts
 * @return array
 */
function filterPostForRegion(string $region, array $posts):array
{
    $fa = [];
    foreach ($posts as $post) {
        if (preg_match('#'.preg_quote($region).'#siu', $post['message'])) {
            $fa[] = $post;
        }
    }
    // file_put_contents('./' . date('dmH_i') . '.txt', var_export($fa, true) );
    return $fa;
}


/**
 * Повертає таймштамп останнього попередження про повітряну тривогу
 * @return int 
 */
function getLastTimestampForAirAlarm():int
{
    global $fileLastAirAlarmTimestamp;
    $stamp = time();
    if (file_exists($fileLastAirAlarmTimestamp)) {
        $stamp = trim( file_get_contents($fileLastAirAlarmTimestamp) );
    } else {
        file_put_contents($fileLastAirAlarmTimestamp, $stamp);
    }

    return (int) $stamp;
}

/**
 * Записує останній таймштамп тривоги
 * @param int $timestamp
 */
function writeAirAlarmTimestamp(int $timestamp)
{
    global $fileLastAirAlarmTimestamp;
    file_put_contents($fileLastAirAlarmTimestamp, $timestamp);
}

/**
 * Головна функція опрацювання повітряних тривог. 
 * Саме ця функція вирішує відправляти повідомлення про тривогу, чи ні.
 * @return void
 */
function mainAirAlarm()
{
    global $apiJsonAirChannel;
    $data = getTelegramChannelPosts($apiJsonAirChannel);
    $alarms = filterPostForRegion('#Черкаська_область', $data);
    
    foreach($alarms as $alarm) {
        if (getLastTimestampForAirAlarm() < (int)$alarm['dateRaw']){
            writeAirAlarmTimestamp((int)$alarm['dateRaw']);
            airAlarm($alarm['start'], $alarm['date']);
        }
    }
}
