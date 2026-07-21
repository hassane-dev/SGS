<?php

function parse_po($filepath) {
    if (!file_exists($filepath)) {
        return null;
    }
    $content = file_get_contents($filepath);
    $lines = explode("\n", $content);
    $entries = [];
    $current_msgid = null;
    $current_msgstr = null;
    $in_msgid = false;
    $in_msgstr = false;

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0) {
            continue;
        }
        if (empty($line)) {
            if ($current_msgid !== null) {
                $entries[$current_msgid] = $current_msgstr;
                $current_msgid = null;
                $current_msgstr = null;
            }
            $in_msgid = false;
            $in_msgstr = false;
            continue;
        }

        if (preg_match('/^msgid\s+"(.*)"$/', $line, $matches)) {
            $current_msgid = $matches[1];
            $in_msgid = true;
            $in_msgstr = false;
        } elseif (preg_match('/^msgstr\s+"(.*)"$/', $line, $matches)) {
            $current_msgstr = $matches[1];
            $in_msgid = false;
            $in_msgstr = true;
        } elseif (strpos($line, '"') === 0) {
            $val = substr($line, 1, -1);
            if ($in_msgid) {
                $current_msgid .= $val;
            } elseif ($in_msgstr) {
                $current_msgstr .= $val;
            }
        }
    }
    if ($current_msgid !== null) {
        $entries[$current_msgid] = $current_msgstr;
    }
    // Remove header entry (empty msgid)
    unset($entries[""]);
    return $entries;
}

$en_entries = parse_po('locale/en_US/LC_MESSAGES/messages.po');
$ar_entries = parse_po('locale/ar/LC_MESSAGES/messages.po');

echo "=== English Catalogue Analysis ===\n";
echo "Total msgid in en_US: " . count($en_entries) . "\n";
$en_non_empty = 0;
foreach ($en_entries as $id => $str) {
    if ($str !== "") {
        $en_non_empty++;
    }
}
echo "Non-empty translations in en_US: " . $en_non_empty . "\n";
echo "Empty/untranslated in en_US: " . (count($en_entries) - $en_non_empty) . "\n\n";

echo "=== Arabic Catalogue Analysis ===\n";
echo "Total msgid in ar: " . count($ar_entries) . "\n";
$ar_non_empty = 0;
foreach ($ar_entries as $id => $str) {
    if ($str !== "") {
        $ar_non_empty++;
    }
}
echo "Non-empty translations in ar: " . $ar_non_empty . "\n";
echo "Empty/untranslated in ar: " . (count($ar_entries) - $ar_non_empty) . "\n";
