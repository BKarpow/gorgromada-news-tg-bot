<?php

include (__DIR__ . '/vendor/autoload.php');
include (__DIR__ . '/config.php');

/**
 * Получает данные из RSS и возражает строку xml
 * 
 * @param string $linkRss - адрес. источника rss
 * @return string - data xml.
 */
function getDataFromRss(string $linkRss):string
{
    return file_get_contents($linkRss);
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
        'description' => htmlspecialchars( (string)$item->description ),
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
    $linkJsonSource = 'https://www.ukr.net/news/dat/cherkasy/2/';
    return json_decode(file_get_contents($linkJsonSource), true  );
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







