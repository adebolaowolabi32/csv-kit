# CSV Kit

[![Packagist version](https://img.shields.io/packagist/v/cynthiaowolabi/csv-kit)](https://packagist.org/packages/cynthiaowolabi/csv-kit)

[![Tests](https://github.com/adebolaowolabi32/csv-kit/actions/workflows/tests.yml/badge.svg)](https://github.com/adebolaowolabi32/csv-kit/actions/workflows/tests.yml)

A framework-independent PHP reader for **one-column email CSV files**, extracted from [Fieldnotes Academy](https://github.com/adebolaowolabi32/fieldnotes-academy). It streams validated rows and exposes stable, one-based data-row cursors for applications that need resumable imports.

## Install

Requires PHP 8.3 or newer. No Laravel dependency and no runtime Composer dependencies.

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

With `after: 1`, the reader skips data row 1 and yields row 2 as `2 => ['email' => 'maya@example.test']`. It does not save checkpoints itself.

## Validation and normalization

- Exactly one header named `email`, case-insensitive; UTF-8 BOM and surrounding header whitespace are accepted.
- Standard CSV quoting and CRLF line endings are supported through PHP's `fgetcsv`.
- Email values are trimmed, lowercased and validated with `FILTER_VALIDATE_EMAIL`, with a maximum of 254 bytes. Lowercasing the entire address is a deliberate import convention; local-part case distinctions are not retained.
- Empty files, wrong headers, extra columns, blank records and invalid addresses throw `InvalidArgumentException`. Data errors include the one-based row number.
- Errors stop iteration; no partial success report or error collection is provided.
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

Keep the source file immutable between attempts. Resume uses a **row cursor**, not a byte offset: skipped rows are scanned again and are not revalidated. Persist the cursor only after successfully committing that row's effects. The application must enforce idempotency and coordinate concurrent workers.

The library does not provide durable checkpoints, queues, database transactions, rollback, account lookup or exactly-once processing. [Fieldnotes Academy's cohort importer](https://github.com/adebolaowolabi32/fieldnotes-academy) demonstrates those responsibilities in Laravel.

Memory usage is bounded by the largest row, not by the full file. This is not a general-purpose CSV mapper or a strict RFC CSV syntax validator; PHP's CSV parser is permissive. Limit input size in the calling application when handling untrusted uploads.

## Development

```sh
composer install
composer test
composer validate --strict
```

CI tests PHP 8.3, 8.4 and 8.5. PHPUnit 11 is used where required by the PHP version; compatible newer environments may resolve PHPUnit 12.

## License and author

MIT © [Cynthia Owolabi](https://adebolaowolabi32.github.io/). Built independently with fictional test data.
