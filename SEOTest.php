<?php

include 'simple_html_dom.php';

$nowDate = date('Y-m-d H-i-s');
$filePostfix = substr($nowDate, 0, 9) . substr($nowDate, 11);
$seoTagsFileName = "seoTagsTest.csv";
$reportFileName = "Report_" . $filePostfix . ".txt";

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_HTTPHEADER, ["Cookie: test=seo"]);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$csvData = getDataFromCSV($seoTagsFileName, $reportFileName);
$f = fopen("Report_" . $filePostfix . ".txt", "a");
foreach ($csvData as $item) {
    $pageUrlFromCSV = $item[0];
    $seoTitleFromCSV = $item[1];
    $seoMetADescriptionFromCSV = $item[2];
    curl_setopt($curl, CURLOPT_URL, $pageUrlFromCSV);
    $response = curl_exec($curl);
    $info = curl_getinfo($curl);


    if ($info['http_code'] !== 200) {
        fwrite($f, "Page " . $pageUrlFromCSV . " is not available - error: " . checkPageErrors($info['http_code']) . "\n");
        continue;
    }
    $html = new simple_html_dom();
    $html->load($response);

    $seoTitleFromPage = $html->find('title', 0);
    $seoMetaDescriptionFromPage = $html->find('meta[name=\'description\']', 0);

    if (($seoTitleFromPage == null) and ($seoMetaDescriptionFromPage == null)) {
        fwrite($f, $pageUrlFromCSV . " - error: there are no seo-tags: 'title', 'meta description'" . "\n");
        continue;
    }

    if (($seoTitleFromPage != null) and ($seoMetaDescriptionFromPage == null)) {
        fwrite($f, $pageUrlFromCSV . " - error: there is no seo-tag: 'meta description'" . "\n");
        continue;
    }

    if (($seoTitleFromPage == null) and ($seoMetaDescriptionFromPage != null)) {
        fwrite($f, $pageUrlFromCSV . " - error: there are no seo-tags: 'title'" . "\n");
        continue;
    }

    $seoTitleFromPage = $seoTitleFromPage->innertext;
    $seoMetaDescriptionFromPage = $seoMetaDescriptionFromPage->content;

    if ($seoTitleFromPage != $seoTitleFromCSV) {
        fwrite($f, $pageUrlFromCSV . " - error: seo-tag 'title' is not updated" . "\n");
    }

    if ($seoMetaDescriptionFromPage != $seoMetADescriptionFromCSV) {
        fwrite($f, $pageUrlFromCSV . "- error: seo-tag 'meta description' is not updated" . "\n");
    }
}
fclose($f);
print ("Выполнение завершено. Отчет о выполнении сохранен в файле " . "Report_" . $filePostfix . ".txt");


function getDataFromCSV($seoTagsFileName, $reportFileName)
{
    checkCSVFileIsExist($seoTagsFileName, $reportFileName);
    if (($handle = fopen($seoTagsFileName, "r")) == false) {
        throwError("Error - the document could not be read, try to recreate it", $reportFileName);
        exit;
    }
    while (($row = fgetcsv($handle, 1000, ";")) !== false) {
        checkCSVFileFormat($row, $reportFileName);
        $fileContent[] = $row;
    }
    checkCSVFileHeader($fileContent, $reportFileName);
    checkCSVFileIsEmpty($fileContent, $reportFileName);
    unset($fileContent[0]);
    return $fileContent;
}

function checkCSVFileFormat($row, $reportFileName)
{
    if (count($row) != 3) {
        throwError(
            "Ошибка в формате файла. Каждая строка должна содержать данные по: Url, Title, Meta description. Разделитель \";\"",
            $reportFileName);
        exit;
    }
}

function checkCSVFileHeader($fileHeader, $reportFileName)
{
    if (($fileHeader[0][0] !== 'Url') or ($fileHeader[0][1] !== 'Title') or ($fileHeader[0][2] !== 'Meta description')) {
        throwError(
            "Заголовок не соответствует формату. Корректный заголовок \"Url,Title,Meta description\"",
            $reportFileName);
        exit;
    }
}

function checkCSVFileIsEmpty($fileContent, $reportFileName)
{
    if (count($fileContent) <= 1) {
        throwError(
            "файл пуст. Заполните, пожалуйста, данные для проверки",
            $reportFileName);
        exit;
    }
}

function checkCSVFileIsExist($seoTagsFileName, $reportFileName)
{
    if (!file_exists($seoTagsFileName)) {
        throwError(
            "К сожалению, файл " . $seoTagsFileName . " со списком страниц не найден. Проверьте наличие файла в директории исполнения скрипта",
            $reportFileName);
        exit;
    }
}

function checkPageErrors($errorCode)
{
    switch ($errorCode) {
        case 300:
            $errorStatus = "300 Multiple Choices";
            break;
        case 301:
            $errorStatus = "301 Moved Permanently";
            break;
        case 302:
            $errorStatus = "302 Moved Temporarily";
            break;
        case 303:
            $errorStatus = "303 See Other";
            break;
        case 304:
            $errorStatus = "304 Not Modified";
            break;
        case 305:
            $errorStatus = "305 Use Proxy";
            break;
        case 307:
            $errorStatus = "307 Temporary Redirect";
            break;
        case 308:
            $errorStatus = "308 Permanent Redirect";
            break;


        case 400:
            $errorStatus = "400 Bad Request";
            break;
        case 401:
            $errorStatus = "401 Unauthorized";
            break;
        case 403:
            $errorStatus = "403 Forbidden";
            break;
        case 404:
            $errorStatus = "404 Not found";
            break;
        case 405:
            $errorStatus = "405 Method Not Allowed";
            break;
        case 406:
            $errorStatus = "406 Not Acceptable";
            break;
        case 407:
            $errorStatus = "407 Proxy Authentication Required";
            break;
        case 408:
            $errorStatus = "408 Request Timeout";
            break;
        case 409:
            $errorStatus = "409 Conflict";
            break;
        case 410:
            $errorStatus = "410 Gone";
            break;
        case 411:
            $errorStatus = "411 Length Required";
            break;
        case 412:
            $errorStatus = "412 Precondition Failed";
            break;
        case 413:
            $errorStatus = "413 Payload Too Large";
            break;
        case 414:
            $errorStatus = "414 URI Too Long";
            break;
        case 415:
            $errorStatus = "415 Unsupported Media Type";
            break;
        case 416:
            $errorStatus = "416 Range Not Satisfiable";
            break;
        case 417:
            $errorStatus = "417 Expectation Failed";
            break;
        case 418:
            $errorStatus = "418 I’m a teapot";
            break;
        case 419:
            $errorStatus = "419 Authentication Timeout";
            break;
        case 421:
            $errorStatus = "421 Misdirected Request";
            break;
        case 422:
            $errorStatus = "422 Unprocessable Entity";
            break;
        case 423:
            $errorStatus = "423 Locked";
            break;
        case 424:
            $errorStatus = "424 Failed Dependency";
            break;
        case 426:
            $errorStatus = "426 Upgrade Required";
            break;
        case 428:
            $errorStatus = "428 Precondition Required";
            break;
        case 429:
            $errorStatus = "429 Too Many Requests";
            break;
        case 431:
            $errorStatus = "431 Request Header Fields Too Large";
            break;
        case 434:
            $errorStatus = "434 Requested host unavailable";
            break;
        case 449:
            $errorStatus = "449 Retry With";
            break;
        case 451:
            $errorStatus = "451 Unavailable For Legal Reasons";
            break;
        case 499:
            $errorStatus = "499 Client Closed Request";
            break;


        case 500:
            $errorStatus = "500 Internal Server Error";
            break;
        case 502:
            $errorStatus = "502 Bad Gateway";
            break;
        case 503:
            $errorStatus = "503 Service Unavailable";
            break;
        case 504:
            $errorStatus = "504 Gateway Timeout";
            break;
        case 505:
            $errorStatus = "505 HTTP Version Not Supported ";
            break;
        case 506:
            $errorStatus = "506 Variant Also Negotiates ";
            break;
        case 507:
            $errorStatus = "507 Insufficient Storage ";
            break;
        case 509:
            $errorStatus = "509 Bandwidth Limit Exceeded ";
            break;
        case 510:
            $errorStatus = "510 Not Extended ";
            break;
        case 511:
            $errorStatus = "511 Network Authentication Required";
            break;
        case 520:
            $errorStatus = "520 Unknown Error";
            break;
        case 521:
            $errorStatus = "521 Web Server Is Down";
            break;
        case 522:
            $errorStatus = "522 Connection Timed Out";
            break;
        case 523:
            $errorStatus = "523 Origin Is Unreachable";
            break;
        case 524:
            $errorStatus = "524 A Timeout Occurred";
            break;
        case 525:
            $errorStatus = "525 SSL Handshake Failed";
            break;
        case 526:
            $errorStatus = "526 Invalid SSL Certificate";
            break;
        default:
            $errorStatus = "Неизвестная ошибка: " . $errorCode;
            break;
    }
    return $errorStatus;
}

function throwError($message, $reportFileName)
{
    $f = fopen($reportFileName, "w");
    fwrite($f, $message);
    fclose($f);
    print ("Выполнение завершено с ошибками. Отчет о выполнении сохранен в файле " . $reportFileName);
}