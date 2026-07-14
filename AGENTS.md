<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== filament/filament rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

</laravel-boost-guidelines>

# Project — MSB Budget Site

## Project overview

This is a bespoke replacement for the Matanuska-Susitna Borough's legacy SharePoint + Power Automate IT budget planning site. It supports the annual IT budget cycle where Division Managers and Department Directors select hardware and software replacements, review costs, and submit their IT budget requests. It is an **internal, authenticated application** — no public access, no anonymous users.

**Scope boundary:** this application handles the **planning and request phase only**. Actuals tracking, encumbrances, and ledger reconciliation stay in New World (Tyler ERP). Do not build features that duplicate New World's role.

**FY28 v1 deadline:** October 1. Prioritize recreating current SharePoint site functionality before adding enhancements.

## Architectural principles

- **Row-per-fiscal-year, never column-per-fiscal-year.** Any table that varies by fiscal year (`software_licenses`, `hardware_model_costs`, `budget_line_items`, etc.) must key on a `fiscal_year` column. Do not suggest schemas with `cost_fy24`, `cost_fy25`, etc.
- **Flexibility over premature normalization.** GL codes, positions, and responsibility mappings must be data-driven, not hardcoded enums.
- **Fail loudly in dev, gracefully in prod.** External integrations (TDX especially) must log errors visibly and never silently swallow failures.
- **Mirror external data, never trust it as source of truth at query time.** TDX assets, and eventually New World positions, are pulled into local mirror tables with a `last_synced_at` and a `raw_payload` JSON column for debugging.
- **Design for the future position/user license tracking add-on** without building it in v1. Keep `positions` table populated-ready, keep `license_count` as a real column on `software_licenses`.

## Domain concepts (critical — read before generating code)

### GL codes (General Ledger)

MSB uses a **5-segment** dotted GL string: `fund.department.division.object.sub_object` (example: `100.115.117.434.100`).

- **Fund** (segment 1): `100` Areawide/General, `200`/`202`/`203` Non-Areawide, `245`–`259` fire service areas, `265`/`293` road/water service areas, `510`/`520` enterprise funds. Determines who is paying.
- **Department** (segment 2): `115` IT, `120` Finance, `130` Planning, `150` Public Works, `160` Emergency Services, `170` Community Dev, `100`/`110` Admin.
- **Division** (segment 3): Cost center within a department. **Division codes are NOT globally unique** — division `117` under department `115` is different from division `117` under any other department. Unique constraint is `(department_code, division_code)`.
- **Object code** (segment 4): Expense category. Examples: `411.x` wages, `421.1` communication/network, `426.6` software, `434` equipment under $24K, `451.1` equipment over $24K. The dollar threshold ($24K or $25K) may shift — treat it as data, not a hardcoded label.
- **Sub-object code** (segment 5): Further categorization. `.100` = IT equipment, `.300` = furniture, `.000` = rollup/parent/unspecified.

**A GL string can be valid at multiple granularities:**

- `100.115.117` — a division rollup
- `100.115.117.434` — a budget-line rollup
- `100.115.117.434.100` — a transaction-level code

The `gl_codes` table must support all three. `object_code` and `sub_object_code_id` are nullable. `code_string` is the canonical dotted display form and is unique.

### Split funding is real and common

A single line item (e.g., one Metronet circuit) can be split across multiple GL codes with percentages. The DSJ→Libraries circuit is split 20/20/20/20/20 across five library GLs. Many fire station lines are split 50/50 or 54/46. **Every budget line item has a many-to-many relationship with GL codes via `line_item_gl_allocations` (percent + amount).** Never model a line item with a single `gl_code_id` foreign key.

### Responsibility ≠ GL structure

Directors want rolled-up views ("all Public Works stuff") regardless of how many GL codes are underneath. Division managers want GL-level detail. Model responsibility scopes separately from the GL hierarchy via a `responsibilities` table with `scope_type` (`fund` | `department` | `division` | `object` | `specific_gl`) and `scope_value`. Do not couple view permissions directly to `gl_codes.id`.

Director and division manager responsibilities may not map cleanly onto GL structures. There are cases where GL structure may provide too much or too little information. Model responsibility scopes separately from GL hierarchy via `responsibilities` table with `scope_type` (`fund` | `department` | `division` | `object` | `specific_gl`) and `scope_value`.

### Hardware comes from Team Dynamix (TDX)

Hardware inventory is authoritative in TDX. A scheduled sync job (initially daily at 3 AM, ramping to more frequent during budget season) pulls asset records into a local `tdx_assets` mirror table. Include `raw_payload` JSON, `last_synced_at`, and a best-effort FK to `hardware_models`. Never write back to TDX from this app.

### Software inventory migrates from SharePoint

The current SharePoint `Software_Inventory` list is being retired. Data lands in `vendors`, `software_products`, and `software_licenses` (row-per-FY). Vendors are shared between software and hardware — do not model them as separate concepts.

### Fiscal year

MSB's FY runs July 1 – June 30. FY27 = July 2026 through June 2027. Always store `fiscal_year` as an integer (`27`, `28`) and derive dates from it, not the reverse.

## Integrations

### TDX (Team Dynamix)

- Scheduled sync via Laravel's task scheduler.
- Base cadence: daily at 03:00 America/Anchorage. Configurable via env for budget season ramp-up.
- Prefer delta pulls (`LastInventoried` filter) once we've confirmed API support; fall back to full refresh.
- Log every sync run to a `sync_runs` table with counts, duration, and errors.
- On failure, alert via Teams webhook and/or email — do not silently retry indefinitely.
- Never write to TDX from this app.

### Entra AD

- Login via `laravel/socialite` or `microsoft-graph-sdk`.
- On login, sync the user's AD group membership to determine edit permissions.
- Do not create local password auth for any user. There is no "forgot password" flow.
- Cache group membership for a short window (e.g., 15 min) to avoid Graph API hammering.

### New World (Tyler ERP) — v2 only

- Not integrated in v1.
- v2 will sync positions from New World into a `positions` mirror table using the same pattern as TDX.
- Do not write New World integration code in v1. Do not scaffold placeholders that reference New World endpoints.

## What NOT to do

- Do not suggest storing GL codes as a single string column without also storing the parsed segments.
- Do not suggest single-GL foreign keys on budget line items. Split funding is required from v1.
- Do not add columns for fiscal years (`fy27_cost`, `fy28_cost`). Use rows.
- Do not build authentication, user management, or "forgot password" flows. Entra owns identity.
- Do not build actuals tracking, encumbrance tracking, PO management, or invoice matching. Those live in New World.
- Do not use emojis in UI copy, commit messages, or documentation.
- Do not commit `.env`, credentials, or any Borough-internal data (real GL strings tied to real budgets, real employee names) to the repo. Fixtures use fake data.
- Do not scaffold code for features that haven't been agreed to. If a requirement is ambiguous, add a `// TODO: confirm with Freedom / Brooke / Katie` comment rather than guessing.

## Domain glossary

- **Areawide** — a service funded by all borough taxpayers regardless of location (fund `100` and similar).
- **Non-areawide** — a service funded only by taxpayers in a specific service area (fire service areas, road service areas, etc.).
- **SSA / RSA** — Special Service Area / Road Service Area. Sub-borough taxing districts.
- **GL string** — the full dotted account code. Also called "account code" in casual conversation.
- **Object code** — segment 4 of the GL string. The expense category.
- **Sub-object code** — segment 5 of the GL string. Further categorization within an object.
- **TDX** — Team Dynamix. IT service management platform where hardware assets live.
- **New World** — Tyler Technologies' ERP. Where actuals, POs, and positions live.
- **Rollup** — an aggregated view across multiple GL codes belonging to a common scope (e.g., all of Public Works).

## When Copilot should ask before generating

- Any change to the `gl_codes` schema or GL parsing logic.
- Any migration that would drop or rename a column on `budget_line_items`, `software_products`, `software_licenses`, or `tdx_assets`.
- Any new integration endpoint.
- Any suggestion to bypass Entra for auth.
- Any suggestion to move actuals tracking into this app.

When in doubt, generate a smaller, more explicit solution and add a `// TODO` for the human to review. This codebase values clarity and maintainability over cleverness.
