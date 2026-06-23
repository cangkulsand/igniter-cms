<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Golden-master (characterization) tests for the search-result render helpers.
 *
 * Purpose: freeze the EXACT HTML currently produced by renderSearchResults() and
 * renderFilterSearchResults() (app/Helpers/cms_helper.php) so the upcoming
 * "Extract Method" refactoring (smell #3 — Duplicate Code) can be proven
 * byte-for-byte behaviour-preserving.
 *
 * How a golden master works: on the FIRST run (or with UPDATE_SNAPSHOTS=1) the
 * current output is written to tests/_snapshots/*.html and the test passes. On
 * EVERY run after that, the freshly generated output is compared against that
 * frozen file — any difference fails the test, signalling that behaviour changed.
 *
 * Determinism: the only non-pure dependency in the render path is the theme
 * colour lookup (getCurrentTheme()/getThemeData()), which reads the `themes`
 * table. We seed one known theme into the in-memory SQLite test DB so the output
 * is identical locally and in CI. Every other helper in the path
 * (getTextSummary, dateFormat, getImageUrl, getBlogCategoryName, base_url, esc)
 * is deterministic given the fixed fixtures below.
 *
 * @internal
 */
final class RenderSearchResultsTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load the same helpers the runtime autoloads that the render path needs.
        helper(['url', 'cms_helper', 'global_functions_helper']);

        // Seed a known theme so colour lookups are deterministic.
        // Under PHPUnit, \Config\Database::connect() resolves to the 'tests'
        // group (SQLite :memory:), the same shared connection the helpers use.
        $db    = \Config\Database::connect();
        $forge = \Config\Database::forge();

        $forge->dropTable('themes', true);
        $forge->addField([
            'path'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'selected'         => ['type' => 'INTEGER', 'constraint' => 1, 'default' => 0],
            'default_color'    => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'heading_color'    => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'accent_color'     => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'surface_color'    => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'contrast_color'   => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'background_color' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
        ]);
        $forge->createTable('themes', true);

        $db->table('themes')->insert([
            'path'             => 'default',
            'selected'         => 1,
            'default_color'    => '#222222',
            'heading_color'    => '#111111',
            'accent_color'     => '#e91e63',
            'surface_color'    => '#ffffff',
            'contrast_color'   => '#000000',
            'background_color' => '#f5f5f5',
        ]);
    }

    protected function tearDown(): void
    {
        \Config\Database::forge()->dropTable('themes', true);
        parent::tearDown();
    }

    public function testRenderSearchResultsNoResultsMatchesGoldenMaster(): void
    {
        $html = renderSearchResults('nonexistent query', [], []);
        $this->assertMatchesGoldenMaster($html, 'renderSearchResults_no_results');
    }

    public function testRenderSearchResultsWithResultsMatchesGoldenMaster(): void
    {
        $html = renderSearchResults('php', $this->blogFixtures(), $this->pageFixtures());
        $this->assertMatchesGoldenMaster($html, 'renderSearchResults_with_results');
    }

    public function testRenderFilterSearchResultsWithResultsMatchesGoldenMaster(): void
    {
        $html = renderFilterSearchResults('php', $this->blogFixtures(), $this->pageFixtures(), 'all');
        $this->assertMatchesGoldenMaster($html, 'renderFilterSearchResults_with_results');
    }

    // --- fixtures -----------------------------------------------------------

    private function pageFixtures(): array
    {
        return [
            ['slug' => 'about-us', 'title' => 'About Us', 'excerpt' => 'Learn about our company and our mission.'],
            ['slug' => 'contact',  'title' => 'Contact',  'excerpt' => ''],
        ];
    }

    private function blogFixtures(): array
    {
        return [
            [
                'slug'           => 'intro-to-php',
                'title'          => 'Intro to PHP',
                'featured_image' => 'uploads/php.jpg',
                'category'       => '',                       // invalid GUID -> "Uncategorized"
                'created_at'     => '2024-01-15 10:30:00',
                'excerpt'        => 'A beginner friendly introduction to PHP programming.',
            ],
            [
                'slug'           => 'php-8-features',
                'title'          => 'PHP 8 Features',
                'featured_image' => '',                        // empty -> getDefaultImagePath()
                'category'       => '',
                'created_at'     => '2024-02-20 08:00:00',
                'content'        => 'A deep dive into the new features shipped with PHP 8.',
            ],
        ];
    }

    // --- golden-master helper ----------------------------------------------

    private function snapshotDir(): string
    {
        return __DIR__ . '/../_snapshots';
    }

    private function assertMatchesGoldenMaster(string $actual, string $name): void
    {
        $dir = $this->snapshotDir();
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . '/' . $name . '.html';

        // First run (or explicit refresh): record the baseline and pass.
        if (getenv('UPDATE_SNAPSHOTS') === '1' || ! is_file($file)) {
            file_put_contents($file, $actual);
            $this->assertFileExists($file, "Golden-master snapshot created: {$name}");

            return;
        }

        // Subsequent runs: output must match the frozen baseline byte-for-byte.
        $this->assertSame(
            file_get_contents($file),
            $actual,
            "Output of {$name}() no longer matches the golden master — external behaviour changed."
        );
    }
}
