<h1 align="center">BFSG-Schnellcheck</h1>

<p align="center">
  Checks a web page for accessibility errors that can be found automatically<br />
  and turns the result into a report you can share by link.
</p>

<p align="center">
  <img src="docs/screenshots/report.png" alt="Report for the W3C demo page with one ring per rule: 54 % of the images have alternative text, the page language is missing, the title is present, none of the 13 form fields is labeled, 91 % of the links have text." />
</p>

Since 28 June 2025 the German Barrierefreiheitsstärkungsgesetz (BFSG) requires many online shops to be accessible. The Schnellcheck gives a first answer in a few seconds, deliberately small: five rules, one page, no login.

## What it checks

| Rule              | What fails                                                                     | WCAG         |
| ----------------- | ------------------------------------------------------------------------------ | ------------ |
| Alternativtext    | An image without an `alt` attribute                                            | 1.1.1        |
| Sprache der Seite | `<html>` without a `lang` attribute                                            | 3.1.1        |
| Seitentitel       | A missing or empty `<title>`                                                   | 2.4.2        |
| Feldbeschriftung  | A form field without a `<label>`, `aria-label` or `aria-labelledby`            | 1.3.1, 4.1.2 |
| Linktext          | A link without text, `aria-label`, `aria-labelledby`, `title` or a named image | 2.4.4, 4.1.2 |

To try it, check the W3C's intentionally broken [demo page](https://www.w3.org/WAI/demos/bad/before/survey.html).

## How it works

```mermaid
flowchart LR
    form["Form"] --> start["AuditService"]
    start --> queue[("Queue")]
    queue --> fetch["UrlFetchService"]
    fetch --> check["AccessibilityCheckService"]
    check --> db[("SQLite")]
    db --> report["Report, polls until done"]
```

The app fetches any URL a visitor enters, so `UrlFetchService` guards against server-side request forgery:

- Only `http` and `https` hosts whose DNS answers are all public addresses.
- The checked IP is pinned for every redirect hop by a Guzzle middleware.
- At most five redirects, 10 seconds and 5 MB of uncompressed HTML.

The check reads the HTML the server sends, without running JavaScript. It finds only some problems and is not a legal assessment.

## Getting started

You need PHP 8.4, Composer and Node 22.

```bash
composer setup     # dependencies, .env, SQLite database, frontend build
composer dev       # server, queue worker and Vite on http://localhost:8000
composer ci:check  # lint, types, Pint, PHPStan and Pest, exactly as in CI
```

## Built with

Laravel 13, Inertia, React 19, Tailwind CSS 4 and shadcn/ui. Every change is an issue and a pull request, the code follows the rules in [`CLAUDE.md`](CLAUDE.md), and the project is built with Claude Code.
