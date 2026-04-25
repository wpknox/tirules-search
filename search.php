<?php include "prefix.php"?>
<header><h1>Search</h1></header>
<article>
<?php
function ti_get_page_files($dir) {
    return glob($dir . '/[RFC]_*.php') ?: [];
}

function ti_extract_page_info($filepath) {
    $raw = file_get_contents($filepath);
    $slug = basename($filepath, '.php');

    if (preg_match('/<header>\s*<h1>(.*?)<\/h1>\s*<\/header>/is', $raw, $m)) {
        $title = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } else {
        $title = ucwords(str_replace('_', ' ', preg_replace('/^[RFC]_/', '', $slug)));
    }

    $text = preg_replace('/<\?php.*?\?>/s', '', $raw);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Normalize dashes and fancy quotes for friendlier searching
    $text = str_replace(["\u{2013}", "\u{2014}", "\u{2019}", "\u{2018}", "\u{201C}", "\u{201D}"], ['-', '-', "'", "'", '"', '"'], $text);
    $text = preg_replace('/\s+/', ' ', trim($text));

    return ['slug' => $slug, 'title' => $title, 'text' => $text];
}

function ti_get_snippets($text, $query, $max = 3) {
    $snippets = [];
    $offset = 0;
    $qlen = strlen($query);

    while (count($snippets) < $max && ($pos = stripos($text, $query, $offset)) !== false) {
        $start = max(0, $pos - 120);
        $end = min(strlen($text), $pos + $qlen + 120);
        $snip = ($start > 0 ? '&hellip;' : '') . htmlspecialchars(substr($text, $start, $end - $start)) . ($end < strlen($text) ? '&hellip;' : '');
        $snip = preg_replace('/(' . preg_quote(htmlspecialchars($query), '/') . ')/i', '<mark>$1</mark>', $snip);
        $snippets[] = $snip;
        $offset = $pos + $qlen;
    }
    return $snippets;
}

$query = trim($_GET['q'] ?? '');
$normalized_query = str_replace(["\u{2013}", "\u{2014}", "\u{2019}", "\u{2018}", "\u{201C}", "\u{201D}"], ['-', '-', "'", "'", '"', '"'], $query);

if ($query !== '') {
    $files = ti_get_page_files(__DIR__);
    $results = [];

    foreach ($files as $file) {
        $info = ti_extract_page_info($file);
        if (stripos($info['text'], $normalized_query) !== false) {
            $results[] = [
                'slug'     => $info['slug'],
                'title'    => $info['title'],
                'snippets' => ti_get_snippets($info['text'], $normalized_query),
            ];
        }
    }

    usort($results, fn($a, $b) => strcmp($a['title'], $b['title']));

    $count = count($results);
    echo '<h2>Results for &ldquo;' . htmlspecialchars($query) . '&rdquo;</h2>';
    if ($count === 0) {
        echo '<p>No results found.</p>';
    } else {
        echo '<p>' . $count . ' page' . ($count === 1 ? '' : 's') . ' found.</p>';
        echo '<ul class="search-results">';
        foreach ($results as $r) {
            $url = '/' . htmlspecialchars($r['slug']) . '?hl=' . urlencode($query);
            echo '<li class="search-result">';
            echo '<a class="search-result-title" href="' . $url . '">' . htmlspecialchars($r['title']) . '</a>';
            foreach ($r['snippets'] as $snip) {
                echo '<p class="search-snippet">' . $snip . '</p>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }
} else {
    echo '<p>Enter a search term above to find relevant rules pages.</p>';
}
?>
</article>
<?php include "suffix.php"?>
