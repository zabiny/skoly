# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Primary: teachers and PE coordinators at Jihomoravský-kraj schools, checking dates, categories, and rules to register their students' team for the season. Secondary: pupils and parents reading the same content to find their own race info.

## Product Purpose

Liga škol is a school-team orienteering competition run by SK Brno Žabovřesky (skoly.zabiny.club). The site is the informational hub: explains how team scoring works, lists the season's races (auto-pulled live from the ORIS API), states categories/rules/logistics, and links to the GDPR/photography notice. Success is a teacher having everything needed to enter their school's team without contacting the organizer.

## Positioning

A regional (JM) school league that feeds into a national finale: the top two schools in the DH79 and DHS categories advance to the republic-wide final. Framed as orienteering promotion for schools first (fun, low-barrier, city-terrain races), not a hardcore competition — the club that runs it is credited but deliberately not the headline.

## Operating Context

- Two rounds per season: autumn okresní (district) kolo, spring krajské (regional) kolo.
- Race dates/venues are entered into ORIS (the Czech orienteering federation's system) by the organizer and pulled onto this page automatically by name-matching "Liga škol" events in region JHM — the page has no admin/CMS of its own.
- Runs on a production server on PHP 7.4 (confirmed hard constraint — PHP 8+-only functions like `str_contains()` will fatal in production even though local dev may run a newer PHP).
- No framework, no build step, no Composer dependencies — plain PHP/HTML/CSS by design, matching the sibling site bll.zabiny.club's approach.
- Deploy is manual: `git push` to GitHub, then `ssh zbm@zabiny.club -p 55007`, `cd /var/www/html/skoly && git pull`.

## Capabilities and Constraints

- Live race list: server-side PHP calls the ORIS API, filters by name + region, caches to a local JSON file (15 min TTL), falls back to a fixed Czech message when no races are listed yet or the API is unreachable — never a raw error or blank section.
- Legal text (GDPR/Fotografování, on the separate `ochrana-udaju.php` page) is copied verbatim from the federation's official rozpis PDF and must not be reworded, unlike the rest of the site's friendlier copy.
- Club name "SK Brno Žabovřesky" is confirmed to appear exactly once per page, in the footer credit line only (linked to zabiny.club) — not in the header, not restated elsewhere in body copy.
- **Planned, not yet built:** old/historical results are being added today (near-term). Also planned (no timeline confirmed): a school standings/results table beyond the current race-list, and a team entry/registration form directly on the page (currently registration happens off-site via ORIS/email).

## Brand Commitments

- Visual identity deliberately distinct from sibling site bll.zabiny.club (dark/red, adult sprint-league audience): this site uses a light green theme (`#0a6b3a`), friendlier and school-oriented.
- Organizer "SK Brno Žabovřesky" is a real club, footer-credited and linked to zabiny.club, but not brand-forward on this surface.

## Evidence on Hand

- Official rozpis PDF (ORIS) is the source of truth for categories, scoring rules, and the legal GDPR/Fotografování text.
- Old/historical results: being added today — no path yet, ask before assuming a format/location.
- No sponsor logos, testimonials, or press on hand.

## Product Principles

- Orienteering-for-schools promotion comes before club branding — the club is credited, not the headline.
- Never show a broken or blank state: missing race data always degrades to a clear Czech fallback message.
- Legal/compliance text is verbatim and untouchable; everything else is written in a friendly, non-competition-heavy tone.
- Production PHP version (7.4) is a hard compatibility constraint on every future change, not just this one.
- No framework/build step is a deliberate constraint, not a gap to fill.
