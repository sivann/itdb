# ITDB2 Test Suite

Simple smoke tests to verify the database migration and basic functionality.

## What's Here

```
tests/
├── bootstrap.php           # PHPUnit initialization
├── SimpleSmokeTest.php    # Basic tests to verify app works
├── TestCase.php           # Base test class (for future tests)
└── Helpers/               # Test utilities (for future use)
    ├── DatabaseSeeder.php
    └── DataFactory.php
```

## Current Tests

**SimpleSmokeTest.php** - Verifies:
- Database connection works
- RackModel can query data
- Column names are correct after migration (size_units, depth_mm)

## Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run with nice output
./vendor/bin/phpunit --testdox

# Run specific test
./vendor/bin/phpunit tests/SimpleSmokeTest.php
```

## Example Output

```
Simple Smoke (App\Tests\SimpleSmoke)
 ✔ Database connection works
 ✔ Rack model works
 ✔ Rack has correct columns

OK (3 tests, 8 assertions)
```

## Adding More Tests

When you need to test new features, create a new test file:

```php
<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use PDO;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use App\Services\DatabaseManager;
use App\Models\YourModel;

class YourFeatureTest extends TestCase
{
    public function testYourFeature(): void
    {
        $pdo = new PDO('sqlite:src/data/itdb.db');
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $db = new DatabaseManager($pdo, $logger);
        $model = new YourModel($db);

        // Your test here
        $result = $model->someMethod();
        $this->assertNotNull($result);
    }
}
```

## For Future: Integration Tests with Test Database

If you want isolated tests that don't touch production data:

1. Use the `TestCase` base class (already included)
2. It creates an in-memory SQLite database
3. Fresh database for each test
4. See `tests/TestCase.php` for helper methods

**Note:** Currently the test schema doesn't match production exactly, so stick with SimpleSmokeTest pattern for now.

## Quick Tips

- Tests use the **real database** at `src/data/itdb.db`
- Make a backup before running tests if concerned
- Tests are read-only (SELECT queries only)
- For write tests, copy database first or use TestCase with in-memory DB
