# Development Principles

Apply these principles on every implementation task — not as a checklist to revisit at the end, but as constraints that shape every decision from the start.

---

## DRY — Don't Repeat Yourself

Every piece of logic must have a single authoritative location. Duplication is not just a style issue — it creates silent divergence bugs when one copy is updated and another is not.

**Rules:**
- If the same logic appears in two places, extract it before writing it a third time.
- Extract shared query scopes into Eloquent local scopes (`scopeActive`).
- Extract shared validation rules into dedicated validation classes or arrays.
- Helper functions shared across the package belong in a dedicated service class or a `src/Support/` helper — not repeated inline.

**Violations to watch for:**
- The same `where` clause written in multiple queries instead of a scope.
- The same validation array duplicated across multiple methods or classes.

---

## SOLID

### S — Single Responsibility Principle

Each class and each method has exactly one reason to change.

**Rules:**
- Public API methods handle only input/output — no business logic inside them.
- Business logic lives in a dedicated class (Action, Service, or Repository), not stuffed into a model or facade.
- A model is responsible for its relationships, casts, scopes, and accessors — not for dispatching jobs or computing business rules.
- If a method does more than one thing (validate AND persist AND notify), split it.

---

### O — Open/Closed Principle

Classes are open for extension, closed for modification.

**Rules:**
- Add behavior through composition (new Action class, new Policy method) rather than editing existing classes.
- Use Laravel's event/listener system to add side-effects to existing flows without touching the original class.
- Extend base classes rather than modifying them; override methods rather than patching logic inline.
- Avoid adding `if ($type === 'x')` branches inside existing methods — introduce a strategy or polymorphism instead.

### L — Liskov Substitution Principle

Subtypes must be fully usable wherever their parent type is expected — no surprises.

**Rules:**
- A child class must not throw exceptions or return different types than its parent contract declares.
- Do not override a method just to make it a no-op or to return `null` where the parent returns a typed value.
- Interface implementations must honour the full contract — including pre/post conditions implied by the interface.
- When using polymorphism, every implementor must expose the same public interface/methods the caller depends on.

### I — Interface Segregation Principle

No class should be forced to depend on methods it does not use.

**Rules:**
- Define narrow, focused interfaces rather than one large `RepositoryInterface` with 20 methods.
- Split a fat interface into role-based contracts: `Searchable`, `Exportable`, `Auditable`.
- When a class only uses 2 of 8 methods on a dependency, that dependency's interface is too broad — split it.
- Use PHP 8 intersection types and union types to express narrow contracts inline where a dedicated interface would be overkill.

### D — Dependency Inversion Principle

High-level modules must not depend on low-level concretions; both depend on abstractions.

**Rules:**
- Type-hint interfaces or abstract classes in constructors, not concrete implementations.
- Bind implementations to interfaces in a `ServiceProvider` — never call `new ConcreteClass()` inline where an interface would do.
- Inject dependencies via constructor (preferred) or method injection — do not resolve from the container with `app()` or `resolve()` inside business logic.
- Use Laravel's contract interfaces (`Illuminate\Contracts\*`) where they exist rather than concrete Facades in testable code.

---

## ACID — Database Transaction Integrity

Every database operation that touches more than one row or table must be wrapped in a transaction that is atomic, consistent, isolated, and durable.

### A — Atomicity

The entire operation succeeds or nothing changes.

**Rules:**
- Wrap all multi-step write operations in `DB::transaction(fn () => { ... })`.
- Always pair `DB::transaction()` with `try...catch(\Throwable $e)`, `Log::error()`, and re-throw so callers know the operation failed.
- Never partially commit — if step 2 fails, step 1 must be rolled back automatically by the wrapping transaction.

```php
try {
    DB::transaction(function () use ($data): void {
        $model = Model::create($data['attributes']);
        $model->relations()->attach($data['relation_id']);
    });
} catch (\Throwable $e) {
    Log::error('Transaction failed', ['error' => $e->getMessage()]);
    throw $e;
}
```

### C — Consistency

The database must be in a valid state before and after every transaction.

**Rules:**
- Enforce constraints at the database level (foreign keys, unique indexes, not-null) — do not rely solely on application-layer validation.
- Always add indexes and unique constraints in migrations for FK columns, filterable columns, and searchable columns.
- Use `$table->foreignId('user_id')->constrained()->cascadeOnDelete()` rather than bare integer columns for foreign keys.
- Validate input before opening a transaction — fail fast with a `ValidationException` before any writes begin.

### I — Isolation

Concurrent transactions must not interfere with each other.

**Rules:**
- Use `lockForUpdate()` or `sharedLock()` on Eloquent queries inside transactions when reading a row you intend to modify to prevent race conditions.
- Do not read data outside a transaction and then use it inside — re-read inside the transaction to prevent stale reads.
- Avoid long-running transactions that hold locks; keep the transaction scope as narrow as possible.

### D — Durability

Committed data must persist even after a failure.

**Rules:**
- Never commit a transaction and then perform side-effects (dispatch events, jobs) inside the same `try` block — dispatch the side-effect after the commit so a failure does not appear to roll back a committed transaction.
- Dispatch queued jobs and events **after** `DB::transaction()` completes, not inside it, unless using `DB::afterCommit()` callback.

```php
DB::transaction(function () use ($model): void {
    $model->updateStatus();
    $model->related()->create([...]);
});

// Safe: dispatched only after commit
ProcessJob::dispatch($model);
```

---

## KISS — Keep It Simple

The best solution is the one a future reader can understand immediately. Complexity is a liability.

**Rules:**
- Write the simplest code that passes the tests. Optimise only when profiling proves a bottleneck exists.
- Prefer a single Eloquent query with eager loading over a clever loop with lazy-loading.
- Prefer a straightforward `if/else` over a pipeline, macro, or higher-order function chain when the logic is linear.
- Do not introduce a design pattern (Repository, Decorator, Strategy) unless two or more concrete use-cases already require it.
- Avoid magic: prefer explicit `Model::create([...])` over dynamic mass-assignment tricks; prefer named scopes over anonymous query macros.
- If you need a comment to explain *what* the code does (not *why*), the code is too complex — simplify it.

---

## YAGNI — You Aren't Gonna Need It

Do not build for requirements that do not exist yet.

**Rules:**
- Do not add configuration flags, feature toggles, or `$options` arrays for single-use code paths.
- Do not add a `type` column to a table "in case we need it later" — add it when there is a concrete requirement.
- Do not create abstract base classes, generic repositories, or plugin systems for a single implementation.
- Do not write fallback or default-handling code for scenarios the package cannot currently produce.
- Do not add optional parameters to methods just because a caller *might* want them someday — add them when a caller needs them.
- Three similar lines of code is better than a premature abstraction. Extract only when the pattern is proven and stable.

---

## Readability

Code is read far more often than it is written. Every decision should make the next reader faster, not the current writer faster.

**Rules:**
- Limit nesting to 3 levels maximum. Use early returns (`guard clauses`) to flatten deeply nested conditionals.
- No magic numbers or magic strings — extract to a named constant, Enum case, or config value.
- One expression per line; do not chain more than 3 method calls on a single line without an intermediate variable.
- Method length: if a method exceeds ~20 lines, it is likely doing too much — split it.
- Avoid abbreviations in names: `$usr`, `$acct`, `$txn` are never acceptable; use `$user`, `$account`, `$transaction`.
- Comments explain *why*, never *what*. If you must explain *what*, the code needs to be rewritten.
- Do not leave dead code (commented-out blocks, unused variables, unreachable branches) — delete it; git history preserves it.

**Examples:**
```php
// Bad — nested, magic number, no guard clause
public function process(Order $order): void
{
    if ($order->status === 'paid') {
        if ($order->total > 1000) {
            // ...
        }
    }
}

// Good — guard clauses, named constant, flat
public function process(Order $order): void
{
    if (! $order->isPaid()) {
        return;
    }

    if ($order->total <= Order::LARGE_ORDER_THRESHOLD) {
        return;
    }

    // ...
}
```

---

## Consistency

The codebase must read as if written by one person. Inconsistency forces every reader to context-switch and second-guess intent.

**Rules:**
- Before writing any new code, read 2–3 sibling files in the same directory to identify the established pattern — then follow it exactly.
- Do not mix paradigms: if using Repository pattern, use it consistently throughout.
- Do not introduce a new library, helper, or abstraction pattern if an equivalent already exists in the package.
- Use consistent vocabulary throughout the package.
- File, class, and method structure must be consistent.
- When the established pattern is clearly wrong, fix it uniformly — do not create a second inconsistent pattern alongside it.

---

## Maintainability

Code must be easy to change safely. The cost of a future modification is a design constraint today.

**Rules:**
- Avoid tight coupling: a class should not need to know implementation details of another class to function correctly.
- Configuration belongs in a publishable `config/` file — never hardcoded inside a class or migration.
- Do not bury business rules inside migration files or helpers — they will not be found or tested.
- Every public method on a service or action class must be covered by a test so changes are caught immediately.
- Prefer composition over inheritance: a class that extends another is harder to change independently than one that receives a dependency.
- Avoid static methods on non-utility classes — they cannot be mocked, injected, or swapped.
- Write code so that any single class can be replaced without changing its callers — this is the practical test of good design.
- Keep dependencies explicit: accept dependencies as constructor or method parameters rather than resolving them internally.

---

## Type Safety

- Every method, function, and closure must have explicit parameter type hints and a return type declaration — including trivial helpers and closures passed to `array_map`, `collect()->map()`, etc.
- Use nullable types (`?string`) and union types (`int|string`) where appropriate; never rely on implicit `null` coercion or untyped mixed returns.
- Use `bool` for any property with exactly two possible states — not a two-value string enum.
- Return typed collections (`Collection`, `EloquentCollection`) rather than plain `array` where Eloquent results are returned.

---

## PHP Code Style

- Always use curly braces for all control structures, even single-line bodies.
- Use PHP 8 constructor property promotion; delete empty zero-parameter `__construct()` bodies unless the constructor is private.
- Use TitleCase for Enum keys: `FavoritePerson`, `Monthly` — not `favorite_person`, `monthly`.
- Every class, method, and property must have a PHPDoc block with `@param` and `@return` tags; use array shape types for complex arrays.
- Never remove or modify docblocks you did not author and that are unrelated to the current change.
- Collapse 3+ `use` statements sharing a namespace prefix into one grouped import; use the parent namespace as a prefix on class references in code.

---

## Naming

- All PHP functions, methods, and variables: `snake_case`.
- Classes: `PascalCase`.
- Constants/Enum keys: `TitleCase`.
- Eloquent relationship methods must be `snake_case`: `savings_product()`, not `savingsProduct()`.
- Names must convey intent without a comment: `isRegisteredForDiscounts()`, not `discount()`.
- Boolean variables and methods start with `is`, `has`, `can`, or `should`: `isActive`, `hasPermission`, `canAccessModule`.

---

## Database & Migrations

- Always add indexes to foreign key columns, searchable columns, and filterable columns.
- Add unique constraints at the database level, not only in application validation.
- Use `foreignId()->constrained()` for FK columns — never a bare `unsignedBigInteger`.
- Use database transactions for operations that modify multiple related records.
- Provide clear migration file names that describe what they do.

---

## Package Architecture

- One class = one responsibility. If a class grows beyond its original concern, split it.
- Check for existing implementations before creating new ones.
- Follow standard Laravel package structure:
  - `src/` - Package source code
  - `config/` - Configuration files
  - `database/` - Migrations and seeders
  - `tests/` - Test files
  - `resources/` - Views and assets (if applicable)

---

## Testing

- Every code change must be covered by a new or updated test; run the affected tests before marking work done.
- Use model factories with custom states where available; never manually construct model attributes in tests.
- Prefer feature tests for integration testing; use unit tests for pure logic with no framework dependencies.
- Write tests that are isolated and do not depend on test execution order.
- Use database transactions in tests to ensure a clean state between tests.

---

## Code Quality

- Implement only what is required. Do not refactor surrounding code unless it directly blocks the task.
- Do not add error handling or fallback logic for scenarios that cannot occur in the current package usage.
- Follow PSR-12 coding standards and use appropriate code formatting tools.
- Keep package dependencies minimal and well-documented.
