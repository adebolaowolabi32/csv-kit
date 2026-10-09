<?php

namespace Fieldnotes\CsvKit;

use Generator;
use InvalidArgumentException;

/** Stateless streaming reader. Persist the row number only after committing its effects. */
final class Reader
{
    /** @return Generator<int,array<string,string>> */
    public function rows(string $path, int $after = 0): Generator
    {
        $file = fopen($path, 'rb');
        if ($file === false) {
            throw new InvalidArgumentException('Cannot open CSV.');
        }
        try {
            $header = fgetcsv($file, 0, ',', '"', '');
            if (! $header) {
                throw new InvalidArgumentException('CSV is empty.');
            }
            $header = array_map(fn ($v) => strtolower(trim((string) $v)), $header);
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            if ($header !== ['email']) {
                throw new InvalidArgumentException('Expected one column named email.');
            }
            $row = 0;
            while (($values = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $row++;
                if ($row <= $after) {
                    continue;
                }
                if (count($values) !== 1 || $values[0] === null) {
                    throw new InvalidArgumentException('Row '.$row.': expected one email value.');
                }
                $email = strtolower(trim($values[0]));
                if (strlen($email) > 254 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Row '.$row.': invalid email address.');
                }
                yield $row => ['email' => $email];
            }
        } finally {
            fclose($file);
        }
    }
}
