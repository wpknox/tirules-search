<?php
// Generates a static HTML copy of the site in ./static/
// Usage: php build.php

define('SRC', __DIR__);
define('OUT', __DIR__ . '/static');

if (!is_dir(OUT)) mkdir(OUT, 0755, true);

// Copy binary assets
foreach (['favicon.png', 'beta.png', 'Helv.otf', 'HelvBd.otf', 'HelvBdIt.otf', 'HelvIt.otf', 'Slider.otf', 'LazBold.ttf'] as $f) {
    if (file_exists(SRC . "/$f")) copy(SRC . "/$f", OUT . "/$f");
}

// style.css has embedded PHP (random background-position) — evaluate it
ob_start();
include SRC . '/style.css';
file_put_contents(OUT . '/style.css', ob_get_clean());

// ── Shared HTML fragments ────────────────────────────────────────────────────

function static_head(string $title): string {
    $hl_js = highlight_js();
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta content="text/html;charset=utf-8" http-equiv="Content-Type"/>
<meta content="utf-8" http-equiv="encoding"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>$title</title>
<link rel="icon" href="favicon.png"/>
<link rel="stylesheet" type="text/css" href="style.css"/>
$hl_js
</head>
<body>

HTML;
}

function static_nav(): string {
    return <<<'HTML'
<nav class="search-bar">
<form action="search.html">
<input type="search" name="q" placeholder="Search rules&hellip;" id="search-input">
<button type="submit">Search</button>
</form>
</nav>

HTML;
}

function static_foot(): string {
    return <<<'HTML'

<footer>
<p><a href="index.html" style="font-size:2em;">Home</a></p>
<p><i>Twilight Imperium</i> &copy; Fantasy Flight Games. Not Affiliated.</p>
<p>Static mirror for offline use. All content and credit belong to
<a href="https://github.com/dangothemango/tirules">dangothemango on GitHub</a>
&mdash; original site at <a href="https://tirules2.com">tirules2.com</a>.</p>
<p><span style="background-color: #fffacd; padding: 0.25em 0.5em; box-shadow: inset 4px 0 0 0 #ffa500; color: black;">Content highlighted in this color</span> reflects official rulings from Dane and are not part of the Living Rules Reference.</p>
</footer>
</body>
</html>
HTML;
}

function highlight_js(): string {
    return <<<'JS'
<script>
(function () {
    var hl = new URLSearchParams(window.location.search).get('hl');
    if (!hl) return;
    window.addEventListener('DOMContentLoaded', function () {
        var article = document.querySelector('article');
        if (!article) return;
        var re = new RegExp(hl.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
        var walker = document.createTreeWalker(article, NodeFilter.SHOW_TEXT, null, false);
        var nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        var first = null;
        nodes.forEach(function (node) {
            if (!node.nodeValue.trim()) return;
            var m, last = 0, frag = document.createDocumentFragment();
            re.lastIndex = 0;
            while ((m = re.exec(node.nodeValue)) !== null) {
                frag.appendChild(document.createTextNode(node.nodeValue.slice(last, m.index)));
                var mark = document.createElement('mark');
                mark.textContent = m[0];
                if (!first) first = mark;
                frag.appendChild(mark);
                last = m.index + m[0].length;
            }
            if (last > 0) {
                frag.appendChild(document.createTextNode(node.nodeValue.slice(last)));
                node.parentNode.replaceChild(frag, node);
            }
        });
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
})();
</script>
JS;
}

// ── Link fixer ───────────────────────────────────────────────────────────────

function fix_links(string $html): string {
    $html = preg_replace_callback('/\bhref="\/([^"]*)"/', function ($m) {
        $path = rtrim($m[1], '/');
        if ($path === '' || $path === '.') return 'href="index.html"';
        return 'href="' . $path . '.html"';
    }, $html);
    $html = str_replace('action="/search"', 'action="search.html"', $html);
    return $html;
}

// ── Build each R_ / F_ / C_ page ────────────────────────────────────────────

function build_page(string $src_file): string {
    $raw = file_get_contents($src_file);

    // Strip the two include lines — all other content is plain HTML
    $raw = preg_replace('/<\?php\s+include\s+"prefix\.php"\s*\?>\n?/', '', $raw);
    $raw = preg_replace('/<\?php\s+include\s+"suffix\.php"\s*\?>\n?/', '', $raw);

    $title = 'Twilight Imperium Rules';
    if (preg_match('/<header>\s*<h1>(.*?)<\/h1>\s*<\/header>/is', $raw, $m)) {
        $title = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')
               . ' - Twilight Imperium Rules';
    }

    return fix_links(static_head($title) . static_nav() . trim($raw) . static_foot());
}

$pages = glob(SRC . '/[RFC]_*.php') ?: [];
foreach ($pages as $file) {
    $slug = basename($file, '.php');
    file_put_contents(OUT . "/$slug.html", build_page($file));
    echo "Built: $slug.html\n";
}

// ── index.php → index.html ───────────────────────────────────────────────────

$index = fix_links(file_get_contents(SRC . '/index.php'));
file_put_contents(OUT . '/index.html', $index);
echo "Built: index.html\n";

// ── Build search index ────────────────────────────────────────────────────────

function extract_text(string $file): array {
    $raw = file_get_contents($file);
    $slug = basename($file, '.php');

    if (preg_match('/<header>\s*<h1>(.*?)<\/h1>\s*<\/header>/is', $raw, $m)) {
        $title = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } else {
        $title = ucwords(str_replace('_', ' ', preg_replace('/^[RFC]_/', '', $slug)));
    }

    $text = preg_replace('/<\?php.*?\?>/s', '', $raw);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(
        ["\u{2013}", "\u{2014}", "\u{2019}", "\u{2018}", "\u{201C}", "\u{201D}"],
        ['-',        '-',        "'",         "'",         '"',        '"'],
        $text
    );
    $text = preg_replace('/\s+/', ' ', trim($text));

    return ['slug' => $slug, 'title' => $title, 'text' => $text];
}

$index_data = array_map('extract_text', $pages);
usort($index_data, fn($a, $b) => strcmp($a['title'], $b['title']));
$index_json = json_encode($index_data, JSON_UNESCAPED_UNICODE);

// ── search.html (fully self-contained, no server needed) ─────────────────────

$search_html = static_head('Search - Twilight Imperium Rules') . static_nav() . <<<HTML
<header><h1>Search</h1></header>
<article id="search-article">
<p>Enter a search term above to find relevant rules pages.</p>
</article>
HTML . static_foot() . <<<SCRIPT

<script>
var INDEX = $index_json;

function esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escRe(s) { return s.replace(/[.*+?^\${}()|[\]\\\\]/g, '\\\\$&'); }

function normalise(s) {
    return s.replace(/[–—]/g, '-')
            .replace(/[‘’]/g, "'")
            .replace(/[“”]/g, '"');
}

function getSnippets(text, q, max) {
    var snippets = [], offset = 0, ql = q.length, tl = text.toLowerCase(), ql2 = q.toLowerCase();
    while (snippets.length < max) {
        var pos = tl.indexOf(ql2, offset);
        if (pos === -1) break;
        var s = Math.max(0, pos - 120), e = Math.min(text.length, pos + ql + 120);
        var snip = (s > 0 ? '…' : '') + esc(text.slice(s, e)) + (e < text.length ? '…' : '');
        snip = snip.replace(new RegExp('(' + escRe(esc(q)) + ')', 'gi'), '<mark>$1</mark>');
        snippets.push(snip);
        offset = pos + ql;
    }
    return snippets;
}

function doSearch(q) {
    q = q.trim();
    var article = document.getElementById('search-article');
    if (!q) { article.innerHTML = '<p>Enter a search term above to find relevant rules pages.</p>'; return; }
    var nq = normalise(q);
    var results = INDEX.filter(function(p) { return p.text.toLowerCase().indexOf(nq.toLowerCase()) !== -1; });
    var html = '<h2>Results for “' + esc(q) + '”</h2>';
    if (results.length === 0) {
        html += '<p>No results found.</p>';
    } else {
        html += '<p>' + results.length + ' page' + (results.length === 1 ? '' : 's') + ' found.</p>';
        html += '<ul class="search-results">';
        results.forEach(function(r) {
            var url = r.slug + '.html?hl=' + encodeURIComponent(q);
            html += '<li class="search-result"><a class="search-result-title" href="' + url + '">' + esc(r.title) + '</a>';
            getSnippets(r.text, nq, 3).forEach(function(s) { html += '<p class="search-snippet">' + s + '</p>'; });
            html += '</li>';
        });
        html += '</ul>';
    }
    article.innerHTML = html;
}

window.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('search-input');
    var form  = document.querySelector('.search-bar form');
    var q = new URLSearchParams(window.location.search).get('q') || '';
    if (q) { input.value = q; doSearch(q); }
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        history.replaceState(null, '', '?q=' + encodeURIComponent(input.value));
        doSearch(input.value);
    });
});
</script>
SCRIPT;

file_put_contents(OUT . '/search.html', $search_html);
echo "Built: search.html\n";
echo "\nDone! Open " . OUT . "/index.html in any browser.\n";
