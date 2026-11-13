# BFSG-Schnellcheck

The app checks a web page for accessibility errors that can be detected automatically and produces a report that can be shared via link. The stack is Laravel with the React Starter Kit (Inertia, React, TypeScript, Tailwind, shadcn/ui), Pest, PHP 8.4 or later and SQLite. The check runs as a queue job with the `database` driver.

Tasks live as issues in the repo. The code rules below apply across all projects and are copied here (as of 2026-11-12).

## Commands

Locally the project runs through Lerd. Lerd is a separate tool, not a typo of Laravel Herd.

```bash
lerd console <command>        # instead of php artisan
lerd composer <command>       # instead of composer
lerd composer test            # Pint check, PHPStan and Pest
lerd console test             # Pest only
lerd composer types:check     # PHPStan (PHP types)
lerd composer ci:check        # everything the CI checks
lerd composer lint:check      # Pint checks without changing anything
lerd php vendor/bin/pint --dirty   # Pint formats the changed files

npm run check                 # lint and format check (Vite+)
npm run check:fix             # fixes what can be fixed automatically
npm run types:check           # tsc (TypeScript types)
npm run build                 # production build
npm run dev                   # Vite dev server
```

Pint runs before every commit. Before a PR, `lerd composer ci:check` passes, exactly as in the CI.

## Workflow

- Issues, pull requests, commits and CLAUDE.md are written in English. Everything users see in the app stays German (see the rule under "Language").
- Every task is an issue with context, tasks and a Definition of Done.
- One branch and one pull request per issue. The branch is named `<number>-<title>`, for example `3-fetch-external-pages-safely`.
- Commits are Conventional Commits in English with the issue number in the scope: `feat(#3): reject private ip addresses`. One topic per commit.
- The PR title has the same format, with the issue title as text: `feat(#3): Fetch external pages safely`.
- The PR text is English and has the sections `## Context`, `## What changed`, `## Why` (only if the diff doesn't show it) and `## How to verify`. The last line is `Closes #3`.
- PRs are merged with a merge commit, never squash or rebase. Ruben merges.
- One agent orchestrates, subagents implement. Before the PR, a fresh subagent reads the diff with the `simplify` skill, and a second one checks only the names. An agent plays through anything visible in the browser once.
- The agent changes files only with the Edit and Write tools, not via shell.

## Names in this project

`Rule`, `report` and `Page` are already taken: `Illuminate\Validation\Rule` and `app/Rules/` stand for validation rules, `report()` reports exceptions, and in Inertia a page is a React page under `resources/js/pages/`. The accessibility rules are therefore called `AccessibilityRule`, a check run is an `Audit` and a violation it finds is a `Finding`. For the checked web page, the code uses `url`, not `page`.

## Code rules

Every PR is checked against these rules. What a tool can check, the tool checks: Pint for PHP, `npm run check` and `tsc` for TypeScript. Some of the examples come from other projects and only show the rule.

### Laravel first

If Laravel already has a tool, helper or pattern for a problem, that is the first approach. A custom solution comes only when the Laravel way doesn't fit, and the reason goes into a code comment or the PR. Example: the HTTP client follows redirects itself, and a Guzzle middleware registered with `Http::withMiddleware()` checks every hop. There is no custom loop over the redirects.

### Language

Under the hood, everything is English: classes, methods, variables, enum cases and their values, columns, route names, commits and code comments. Only what users see is German: labels, texts, mails, error messages, URL paths and query parameters. So an enum is `Role::Warehouse` with `label()` returning "Lager", not `Role::Lager`.

### Names

A name says what a method does or returns. Whoever reads the code should not have to open the method to understand it.

- No vague verb: `parseAndValidate()` instead of `inspect()`, `prefillForEditing()` instead of `fillFrom()`, `resetInputs()` instead of `settle()`.
- No word that already means something else in the project or the framework. "Storage" means the warehouse in a warehouse management app. `fill` and `report()` are taken in Laravel, and `route()` builds a URL in Laravel. A model is not called `$value`, because enums have `->value`.
- A name follows its siblings. If `changePassword()` and `changeRole()` exist, switching the theme is `changeTheme()`, not `setTheme()`.
- A bool is named `is…` or `has…`, a string with a code is named `…Code`: `$isIntake`, `$openBinCode`. A noun like `$intake` or `$open` reads like an object.
- Two private methods with one caller each that together do one job are one method.
- A repository method asks the question the caller has. If the caller wants to know whether something exists, it is called `has…()` and uses `exists()`. Counting and then checking `> 0` is more awkward and slower.

### Enums

Fixed sets of values such as statuses, types, roles, states or sort options are always a backed enum. No loose strings, no string constants on the model.

- The model casts the field to the enum.
- Labels and groupings live on the enum (`label()`, `cases()`), not spread across views and components.
- If an external system forces strings, convert with `->value` at that boundary.
- If there is no enum yet for a set of values, we create one when we touch that place.

### Controllers

A controller stays on the request layer: validate the request (Form Request), call a service or repository, return a response. No queries, no business logic, no loops over models.

### Repositories and services

- **Repository:** read access and queries, including eager loading and filters. Returns models or collections and never writes.
- **Service:** business logic, writes, imports and integration of external systems. Reads through repositories and writes no queries of its own.
- Controllers, commands and jobs only call repositories and services. The logic does not live in them.
- If a repository or service already exists for something, it is extended. No second one next to it.
- A service stands for a business area (for example Catalog, Storage, Customer), not for a model.
- A single scope plus a terminal call (`first`, `firstOrFail`, `get`) may stand directly in the caller, for example `Page::byKey($key)->firstOrFail()`. As soon as more is added (more conditions, eager loading, pagination, raw SQL), the query belongs in the repository.
- Repository methods are only created once there is a caller. When the last caller goes away, the method goes too.
- Methods are named after what they return, not after the caller: `binContents`, not `contentsForBinPage`.
- No interfaces for repositories or services as long as nobody swaps the data source.
- Injected services and repositories are named like their class: `StorageService $storageService`, not `$storage`.

### Scopes

Conditions that occur more than once are a scope on the model, and the repository only combines them. A search that several pages need is one scope, not a copy per page.

This also applies to business conditions on an attribute, for example "the product is a donation". If a caller needs the condition on an already loaded model, a method `is…()` with the same meaning sits next to the scope. The condition is never rebuilt at the call site.

Define scopes with the `#[Scope]` attribute, not with the `scope` prefix. The method is `protected`, is named like the call and returns nothing. An existing scope with the prefix is converted as soon as we touch the place.

```php
use Illuminate\Database\Eloquent\Attributes\Scope;

/**
 * Artworks whose number, title or artist name contains the term, ignoring case.
 */
#[Scope]
protected function search(Builder $query, string $term): void
{
    $like = '%'.addcslashes($term, '\\%_').'%';

    $query->where(fn (Builder $query) => $query
        ->where('artworks.number', 'ilike', $like)
        ->orWhere('artworks.title', 'ilike', $like)
        ->orWhereHas('artist', fn (Builder $query) => $query->where('artists.name', 'ilike', $like)));
}
```

### Closures in the query builder

Callbacks in `when()`, `where()`, `whereHas()`, `with()` and similar builder methods are written as a full closure with a typed builder. An arrow function is only allowed if it contains exactly one condition.

```php
// Right
->when($filters['search'] ?? null, function (Builder $query, string $search) {
    $query->search($search);
})

// Allowed: one condition
->when($filters['artist_id'] ?? null, fn ($query, $value) => $query->where('artworks.artist_id', $value))
```

### Closures outside the query builder

A closure that returns only one expression is written as an arrow function, for example in `DB::transaction()`, `map()` or `each()`. It captures the variables itself, so the `use` list goes away. Several statements or an `if` still need `function () use (...)`.

```php
// Wrong: use list for a single expression
return DB::transaction(function () use ($artworks, $bin, $note, $user) {
    return $artworks->map(fn (Artwork $artwork) => $this->moveToBin($artwork, $bin, $note, $user));
});

// Right: arrow functions
return DB::transaction(fn () => $artworks->map(fn (Artwork $artwork) => $this->moveToBin($artwork, $bin, $note, $user)));
```

### Eager loading

Applies as soon as a relation is loaded with `with()` and needs a condition or an ordering.

- The constraint is a closure inside `with()`, as in the Laravel docs under "Constraining Eager Loads". The closure is written out in full and type-hints the contract `Illuminate\Contracts\Database\Eloquent\Builder`.
- If the same constraint is needed in several places, it becomes its own relation on the model that builds on the base relation. It is then loaded without a closure.
- No `orderBy` in the base relation. That ordering would come before every other one and would override orderings in the repository.

```php
use Illuminate\Contracts\Database\Eloquent\Builder;

return $rack->bins()
    ->with([
        'artworks' => function (Builder $query) {
            $query->orderBy('number');
        },
        'artworks.artist',
        'artworks.media',
    ])
    ->get();
```

Used in several places, as its own relation:

```php
/**
 * The artworks of the bin by number.
 *
 * @return HasMany<Artwork, $this>
 */
public function artworksByNumber(): HasMany
{
    return $this->artworks()->orderBy('number');
}

// Repository
return $rack->bins()->with(['artworksByNumber.artist', 'artworksByNumber.media'])->get();
```

### Migrations

Every migration has a `down()` that really reverts the step. A dropped column comes back in `down()` with its type and default.

A column or table that goes away is dropped by the release after. In the first release, the code no longer reads or writes it, and in the next one a migration removes it. The same applies to a rename: create and fill the new column, drop the old one later. That way, after a rollback, the previous version also runs on the new schema.

### Control flow

Collections are the first choice, also across several steps. Readability comes before brevity: one step per line, and what happens per element sits in a small method with a descriptive name instead of inside the closure. If you have to take a line apart in your head to see what comes out, it is too dense. A `foreach` loop is the exception. There are no hand-written counters or helper variables, the collection has `count()`, `sum()` or `filter()` for that.

```php
$results = collect(AccessibilityRule::cases())
    ->mapWithKeys(fn (AccessibilityRule $rule) => [
        $rule->value => $this->checkRule($rule, $document),
    ]);

return [
    'findings' => $results->flatMap(fn (array $result) => $result['findings'])->all(),
    'checkedCounts' => $results->map(fn (array $result) => $result['checkedCount'])->all(),
];
```

`&&` and `||` only appear in conditions, never as a replacement for `if`. So not `$slugs && Urls::syncSlugs(...)`, but an `if` block. This also applies to validation closures: prefer `function () use (...)` with `if` over `fn () => ... && $fail()`.

### Comments

Comments explain why something is the way it is. What the next line does is shown by the code itself.

A docblock describes the contract of a method: what it returns or does, what it expects and why it is built that way. Who calls it, where it is used or which page it is meant for does not belong in it. "Find Usages" and grep show that. The check: does the docblock stay true if a caller is added or removed tomorrow? The same goes for ticket numbers and time words like "currently" or "so far", which belong in commits. A why without a caller may stay.

```php
// Wrong: names the callers and goes stale with the next one
/**
 * Artworks whose number, title or artist name contains the term.
 * Shared by the Werkliste, the bin page and the rack search.
 */

// Right: only describes what the method does
/**
 * Artworks whose number, title or artist name contains the term, ignoring case.
 */
```

### React and Inertia

React renders what the controller delivers. Loading, filtering, sorting, validating and saving data stays in Laravel. The sections on controllers, repositories and services apply unchanged, React only replaces the views.

- Pages live under `resources/js/pages/`, reusable components under `resources/js/components/`. One component per file, file names in kebab-case as in the Starter Kit.
- Every page has a props type that matches what the controller passes. Types shared by several pages live under `resources/js/types/`. No `any`, and no `as` to silence a type error.
- Only the fields the page displays go to the browser, as an array or through an API resource, never as a whole model with all columns.
- URLs come from Wayfinder (`@/routes`, `@/actions`) and are never built as strings. Forms use Inertia's `<Form>` component with `.form()` from Wayfinder.
- In forms, the error messages come from the Form Request. There is no second validation in JavaScript. HTML attributes like `required` or `type="url"` are fine.
- State in the browser exists only for the interface: open and closed, focus, input before submitting. Search, filters and sorting live in the URL as query parameters with a German name and come back from the server as props.
- The page fetches new data through Inertia (`router.reload()` with `only`, `usePoll`), not with `fetch` against its own endpoints.
- No `useMemo`, `useCallback` or `memo`, the React Compiler handles that.
- `useEffect` only to stay in sync with something outside React, such as a browser API or a timer. What can be computed from props is computed during rendering.
- There are no React tests. The CI checks types and lint, and feature tests check the behavior through the props with `assertInertia()`.

### shadcn/ui and Tailwind

- UI building blocks come from shadcn/ui, added with `npx shadcn add` under `resources/js/components/ui/`. If a building block exists for an element, a page contains no native `<button>`, `<input>` or `<select>`.
- The files under `components/ui/` belong to the project. If a variant is missing, it is added to the building block with `cva`. Classes at the call site only control spacing, width and position, not the look.
- For colors there are only the theme tokens, for example `bg-primary` or `text-muted-foreground`. No Tailwind palettes like `bg-red-500` and no fixed values like `text-[#333]`, because the theme handles light and dark.
- Classes are merged with `cn()` from `@/lib/utils`, not with template strings.
- If a piece of markup looks the same in two places, it becomes its own component.

### Tests

- Feature tests with Pest only: fixed input, expected result from the app's point of view. We do not test framework behavior, enums or casts.
- Keep them lean, no collection of edge cases. One test per behavior that can break.
- Every feature has feature tests. The agent writes them with the feature in the same PR.
- Business code is tested: services, policies, jobs and webhooks. We do not write browser or UI tests for now.
- Ask of every test: does it fail when a business rule breaks? If only a framework change can break it, it is not written.
- There is one file per feature under `tests/Feature/<Area>/`. The test name describes the behavior from the user's point of view. A test creates its data with factories, calls the entry point the app also uses and checks the business result.
- External systems are replaced with `Http::fake()`.
- A test checks what its name says. "Prefills the search" checks the value in the field. A word appearing somewhere on the page is not enough for that.
- Test data comes from factories. If a model has none, one is created. There are no global helper functions per test file: Pest loads all files into one process, and a second helper for the same thing appears unnoticed.
