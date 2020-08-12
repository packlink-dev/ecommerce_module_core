<?php
/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

function toCsv()
{
    $en = json_decode(file_get_contents(__DIR__ . '/en.json'), true);
    $es = json_decode(file_get_contents(__DIR__ . '/es.json'), true);
    $de = json_decode(file_get_contents(__DIR__ . '/de.json'), true);
    $fr = json_decode(file_get_contents(__DIR__ . '/fr.json'), true);
    $it = json_decode(file_get_contents(__DIR__ . '/it.json'), true);

    $resultPath = __DIR__ . '/translations.csv';
    if (file_exists($resultPath)) {
        unlink($resultPath);
    }

    $f = fopen($resultPath, 'cb+');
    fputcsv($f, array('Group', 'key', 'English', 'Spanish', 'German', 'French', 'Italian'));

    foreach ($en as $group => $translations) {
        foreach ($translations as $key => $value) {
            $line = array();
            $line[] = $group;
            $line[] = $key;
            $line[] = $value;
            $line[] = isset($es[$group][$key]) ? $es[$group][$key] : '';
            $line[] = isset($de[$group][$key]) ? $de[$group][$key] : '';
            $line[] = isset($fr[$group][$key]) ? $fr[$group][$key] : '';
            $line[] = isset($it[$group][$key]) ? $it[$group][$key] : '';

            fputcsv($f, $line);
        }
    }

    fclose($f);
}

function fromCsv()
{
    $sourcePath = __DIR__ . '/translations.csv';
    if (!file_exists($sourcePath)) {
        echo "no source file\n";
        exit();
    }

    $f = fopen($sourcePath, 'rb+');
    // skip header
    fgetcsv($f);

    $en = $es = $de = $fr = $it = array();

    while ($line = fgetcsv($f)) {
        $en[$line[0]][$line[1]] = $line[2];
        $es[$line[0]][$line[1]] = $line[3];
        $de[$line[0]][$line[1]] = $line[4];
        $fr[$line[0]][$line[1]] = $line[5];
        $it[$line[0]][$line[1]] = $line[6];
    }

    fclose($f);

    exportJson('en', $en);
    exportJson('es', $es);
    exportJson('de', $de);
    exportJson('fr', $fr);
    exportJson('it', $it);
}

function exportJson($lang, $data)
{
    file_put_contents(
        __DIR__ . "/$lang.json",
        str_replace(
            '    ',
            '  ',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        )
    );
}

// fromCsv();
// toCsv();