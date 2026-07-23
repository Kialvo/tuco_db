<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Hosts a test run is allowed to touch. Anything else is assumed to be a
     * real environment.
     */
    private const LOCAL_HOSTS = ['127.0.0.1', 'localhost', '::1', ''];

    /**
     * Abort the run if the test database is not a throwaway one.
     *
     * This project's .env points at the LIVE production host, and phpunit.xml
     * is one commented-out line away from letting tests inherit it. Traits like
     * RefreshDatabase then run migrate:fresh, which drops every table — no
     * prompt, no --force. Guarding here rather than in setUp() is deliberate:
     * the parent boots the application and THEN runs the trait hooks, so a
     * check in setUp() would fire after the tables were already gone. By
     * overriding setUpTraits() we run with a booted app (config is readable)
     * but before refreshDatabase() is reached.
     *
     * @return array
     */
    protected function setUpTraits()
    {
        $this->guardAgainstNonLocalDatabase();

        return parent::setUpTraits();
    }

    /**
     * Fail loudly when the resolved connection points somewhere real.
     */
    protected function guardAgainstNonLocalDatabase(): void
    {
        $name = (string) config('database.default');
        $connection = (array) config('database.connections.'.$name, []);
        $driver = $connection['driver'] ?? '(none)';

        if ($driver === 'sqlite') {
            $database = (string) ($connection['database'] ?? '');

            // :memory: is discarded per-test; a file under the project is a
            // local scratch DB. Anything else is someone's real sqlite file.
            if ($database === ':memory:' || str_starts_with($database, base_path())) {
                return;
            }

            $this->abortUnsafeDatabase($name, $driver, $database);
        }

        $host = (string) ($connection['host'] ?? '');

        if (in_array($host, self::LOCAL_HOSTS, true)) {
            return;
        }

        $this->abortUnsafeDatabase($name, $driver, $host);
    }

    private function abortUnsafeDatabase(string $name, string $driver, string $target): never
    {
        throw new RuntimeException(implode(PHP_EOL, [
            'Refusing to run tests against a non-local database.',
            '',
            "  connection : {$name} ({$driver})",
            "  target     : {$target}",
            '',
            'Tests drop and recreate the schema (RefreshDatabase runs migrate:fresh),',
            'so pointing them at a remote host destroys its data. Restore the',
            'DB_CONNECTION / DB_DATABASE overrides in phpunit.xml, or point DB_HOST',
            'at a local database before running the suite.',
        ]));
    }
}
