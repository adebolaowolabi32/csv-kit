<?php

namespace Fieldnotes\CsvKit\Tests;

use Fieldnotes\CsvKit\Reader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    private array $files = [];

    private function csv(string $text): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv-test-');
        file_put_contents($path, $text);
        $this->files[] = $path;

        return $path;
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $path) {
            unlink($path);
        }

        parent::tearDown();
    }

    public function test_streams_bom_quotes_normalization_and_stable_cursor(): void
    {
        $path = $this->csv("\xEF\xBB\xBFemail\r\n\"ONE@example.test\"\r\ntwo@example.test\r\n");
        $reader = new Reader;
        $this->assertSame([1 => ['email' => 'one@example.test'], 2 => ['email' => 'two@example.test']], iterator_to_array($reader->rows($path)));
        $this->assertSame([2 => ['email' => 'two@example.test']], iterator_to_array($reader->rows($path, 1)));
    }

    public function test_rejects_wrong_header(): void
    {
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array((new Reader)->rows($this->csv("name\nAlex\n")));
    }

    public function test_rejects_malformed_rows_with_row_number(): void
    {
        $this->expectExceptionMessage('Row 2');
        iterator_to_array((new Reader)->rows($this->csv("email\none@example.test\nnot-an-email\n")));
    }

    public function test_rejects_extra_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array((new Reader)->rows($this->csv("email\none@example.test,extra\n")));
    }

    public function test_rejects_empty_file(): void
    {
        $this->expectExceptionMessage('CSV is empty.');
        iterator_to_array((new Reader)->rows($this->csv('')));
    }
    public function test_rejects_blank_data_rows(): void
    {
        $this->expectExceptionMessage('Row 2: expected one email value.');
        iterator_to_array((new Reader)->rows($this->csv("email\none@example.test\n\n")));
    }

    public function test_header_only_file_has_no_data_rows(): void
    {
        $this->assertSame([], iterator_to_array((new Reader)->rows($this->csv("email\n"))));
    }

    public function test_cursor_at_or_beyond_end_yields_no_rows(): void
    {
        $path = $this->csv("email\none@example.test\n");
        $this->assertSame([], iterator_to_array((new Reader)->rows($path, 1)));
        $this->assertSame([], iterator_to_array((new Reader)->rows($path, 10)));
    }

    public function test_resume_preserves_original_row_number_for_errors(): void
    {
        $this->expectExceptionMessage('Row 3: invalid email address.');
        iterator_to_array((new Reader)->rows($this->csv("email\none@example.test\ntwo@example.test\ninvalid\n"), 2));
    }

    public function test_normalizes_header_and_email_whitespace(): void
    {
        $this->assertSame([1 => ['email' => 'alex@example.test']], iterator_to_array(
            (new Reader)->rows($this->csv(" EMAIL \n  Alex@EXAMPLE.test  \n"))
        ));
    }
}
