# CSV Kit

[![Packagist version](https://img.shields.io/packagist/v/cynthiaowolabi/csv-kit)](https://packagist.org/packages/cynthiaowolabi/csv-kit)

[![Tests](https://github.com/adebolaowolabi32/csv-kit/actions/workflows/tests.yml/badge.svg)](https://github.com/adebolaowolabi32/csv-kit/actions/workflows/tests.yml)

A framework-independent PHP reader for **one-column email CSV files**, extracted from [Fieldnotes Academy](https://github.com/adebolaowolabi32/fieldnotes-academy). It streams validated rows and exposes stable, one-based data-row cursors for applications that need resumable imports.

## Install

Requires PHP 8.3 or newer. The library runs on PHP alone.

Install from [Packagist](https://packagist.org/packages/cynthiaowolabi/csv-kit):

```sh
composer require cynthiaowolabi/csv-kit:^1.0
```

## Use

```php
use Fieldnotes\CsvKit\Reader;

foreach ((new Reader)->rows('/private/learners.csv', after: $savedCursor) as $row => $data) {
    // In your database transaction: apply the row and persist $row together.
    enroll($data['email']);
}
```

Expected input:

```csv
email
alex@example.test
maya@example.test
```

With `after: 1`, the reader skips data row 1 and yields row 2 as `2 => ['email' => 'maya@example.test']`. The calling application saves checkpoints.

## Validation and normalization

- Exactly one header named `email`, case-insensitive; UTF-8 BOM and surrounding header whitespace are accepted.
- Standard CSV quoting and CRLF line endings are supported through PHP's `fgetcsv`.
- Email values are trimmed, lowercased and validated with `FILTER_VALIDATE_EMAIL`, with a maximum of 254 bytes. Lowercasing the entire address is a deliberate import convention; this also normalizes the local part.
- Empty files, wrong headers, extra columns, blank records and invalid addresses throw `InvalidArgumentException`. Data errors include the one-based row number.
- Iteration stops at the first error.
- The file handle closes when the generator finishes or is destroyed.

Catch errors around iteration, since generator execution starts when it is consumed:

```php
try {
    foreach ((new Reader)->rows($path) as $row => $data) {
        // Process each validated row.
    }
} catch (\InvalidArgumentException $error) {
    // Report the validation error and keep the last committed cursor.
}
```

## Resume contract and boundaries

Keep the source file immutable between attempts. Resume uses a saved **row number**: the reader scans past earlier rows and validates subsequent rows. Persist the cursor only after successfully committing that row's effects. The application must enforce idempotency and coordinate concurrent workers.

The calling application manages durable checkpoints, queues, database transactions, rollback, account lookup and idempotency. [Fieldnotes Academy's cohort importer](https://github.com/adebolaowolabi32/fieldnotes-academy) demonstrates those responsibilities in Laravel.

Memory usage scales with the largest row. The reader handles email CSV files using PHP's permissive CSV parser. Limit input size in the calling application when handling untrusted uploads.

## Development

```sh
composer install
composer test
composer validate --strict
```

CI tests PHP 8.3, 8.4 and 8.5. PHPUnit 11 is used where required by the PHP version; compatible newer environments may resolve PHPUnit 12.

## License and author

MIT © [Cynthia Owolabi](https://adebolaowolabi32.github.io/). Built independently with fictional test data.
