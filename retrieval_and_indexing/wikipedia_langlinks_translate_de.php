<?php

/*
 * This script uses the Wikipedia API to translate the title.
 */

$filename = "relevant_articles_credibility.txt";

$translations = array();

$count = 0;
$countTranslations = 0;

if (($handle = fopen('./wikipedia/' . $filename, 'r')) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ';')) !== FALSE) {
        $title = str_replace("_", " ", $row[0]);
        $langlinks = file_get_contents("http://en.wikipedia.org/w/api.php?action=query&titles=" . urlencode($title) . "&prop=langlinks&format=xml");
        $xml = simplexml_load_string($langlinks);
        $de = xpath($xml, "/api/query/pages/page/langlinks/ll[@lang='de']/text()");

        array_push($translations, $title . ";" . $row[1] . ";" . $de);
        if ($de != "") {
            $countTranslations++;
        }
        echo ++$count . ": " . $title . " -> '" . $de . "' (" . $countTranslations . " translations)\n";
    }
    fclose($handle);
}

//foreach ($article_labels as $label) {
//    $title = str_replace("_", " ", $label);
//    $langlinks = file_get_contents("http://en.wikipedia.org/w/api.php?action=query&titles=" . urlencode($title) . "&prop=langlinks&format=xml");
//    $xml = simplexml_load_string($langlinks);
//    $de = xpath($xml, "/api/query/pages/page/langlinks/ll[@lang='de']/text()");
//
//    array_push($translations, $title . ";" . $de);
//    if ($de != "") {
//        $countTranslations++;
//    }
//    echo ++$count . ": " . $title . " -> '" . $de . "' (" . $countTranslations . " translations)\n";
//}

$output_file_content = implode($translations, "\n");
file_put_contents("./wikipedia/de_translated_" . $filename, $output_file_content);

function xpath($xml, $xpath_expression, $return_entire_array = false) {
    $result_array = $xml->xpath($xpath_expression);
    if ($return_entire_array == false) {
        if (isset($result_array[0])) {
            return $result_array[0];
        } else {
            return "";
        }
    } else {
        return $result_array;
    }
}
