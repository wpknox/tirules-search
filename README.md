# TI4 Rules Reference

A web reference for the rules of *Twilight Imperium: Fourth Edition*, including the *Prophecy of Kings* expansion and Codices I–IV.

## Credit

All rules content and original site design are the work of **[dangothemango](https://github.com/dangothemango/tirules)**, available at [tirules2.com](https://tirules2.com). This repository is a fork of that project. All credit for the rules reference belongs to the original author.

---

## What was added in this fork

- **Search bar** — appears at the top of every page, linking to a search results page (`/search`)
- **Full-text search** — scans all rules, faction, and component pages and returns matching results with highlighted snippets
- **Click-through highlighting** — clicking a search result navigates to the relevant page and automatically scrolls to and highlights every match
- **Static site builder** (`build.php`) — generates a fully self-contained `docs/` folder of plain HTML files that can be opened in any browser without a server or PHP

---

## Running with PHP (recommended for development)

PHP's built-in server works out of the box using the included `router.php`.

**Prerequisites:** PHP 8.0 or later (`brew install php` on macOS)

```bash
cd tirules
php -S localhost:8080 router.php
```

Then open [http://localhost:8080](http://localhost:8080) in your browser. Press `Ctrl+C` to stop the server.

---

## Generating the static site

The static build produces a `docs/` folder of plain `.html` files — no server or PHP required to view them.

**Build:**

```bash
cd tirules
php build.php
```

This generates `docs/index.html` and one `.html` file per rules/faction/component page, plus a self-contained `docs/search.html` with a fully client-side search engine.

**Rebuild any time** the source PHP files change by re-running `php build.php`.

---

## Viewing the static site locally

### Option 1 — Open directly in a browser (simplest)

Double-click `docs/index.html`, or open it from your browser's *File > Open* menu. All pages and search work from the local filesystem with no server needed.

### Option 2 — Serve with Python (if Python is available)

```bash
cd tirules/static
python3 -m http.server 8080
```

Then open [http://localhost:8080](http://localhost:8080).

### Option 3 — Serve with PHP

```bash
cd tirules/static
php -S localhost:8080
```

Then open [http://localhost:8080](http://localhost:8080).

---

## Distributing the static site

The `docs/` folder is entirely self-contained. Copy it to a USB drive, share it as a zip, or host it on any static file host (GitHub Pages, Netlify, etc.) — no build step or server configuration required on the receiving end.
