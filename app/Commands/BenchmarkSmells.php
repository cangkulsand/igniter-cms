<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Micro-benchmark harness for the B6 (Performance Comparison) task.
 *
 * Measures average execution time (via hrtime) and peak memory for the
 * functions targeted by refactoring. Run this on the ORIGINAL code to get
 * the "before" numbers, then again after refactoring for the "after" numbers.
 *
 * Usage:
 *   php spark benchmark:smells
 *   php spark benchmark:smells --iterations 5000
 *
 * NOTE: timing must be run LOCALLY (Laragon) for stable numbers — CI runners
 * are too noisy. Deterministic metrics (LOC, complexity, duplication) come
 * from SonarCloud instead.
 */
class BenchmarkSmells extends BaseCommand
{
    protected $group       = 'Benchmark';
    protected $name        = 'benchmark:smells';
    protected $description = 'Benchmarks the refactoring-target functions (time + memory) for the B6 comparison.';
    protected $usage       = 'benchmark:smells [--iterations N]';
    protected $options     = ['--iterations' => 'Iterations per benchmark (default 2000)'];

    public function run(array $params)
    {
        $iterations = (int) (CLI::getOption('iterations') ?: 2000);

        helper(['cms', 'auth', 'global_functions', 'tracking', 'plugins']);

        CLI::write('Benchmarking refactoring targets — ' . $iterations . ' iterations each', 'yellow');
        CLI::write(str_repeat('-', 78));

        $results = [];

        // --- #4: logActivity() — Long Parameter List ---
        // Wrapped in a rolled-back transaction so it does NOT pollute activity_logs.
        $results[] = $this->bench('#4 logActivity()', $iterations, function () {
            $db = \Config\Database::connect();
            $db->transBegin();
            logActivity(0, 'benchmark', 'bench', '', 'bench', '0', '', '');
            $db->transRollback();
        });

        // --- #3: render functions — Duplicate Code ---
        // Empty result sets measure the fixed function overhead (baseline).
        // For representative load, seed real rows in the Phase 1 baseline step.
        $results[] = $this->bench('#3 renderSearchResults()', $iterations, function () {
            renderSearchResults('test', [], []);
        });
        $results[] = $this->bench('#3 renderFilterSearchResults()', $iterations, function () {
            renderFilterSearchResults('test', [], [], '');
        });

        $this->printTable($results);

        CLI::newLine();
        CLI::write('Tip: record these as the "before" row, refactor, then re-run for "after".', 'green');
    }

    /**
     * Runs $fn $iterations times and returns timing + memory stats.
     * Failures are captured (not fatal) so one bad fixture doesn't abort the run.
     */
    private function bench(string $label, int $iterations, callable $fn): array
    {
        $memStart = memory_get_peak_usage(true);

        try {
            // warm-up
            $fn();

            $start = hrtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $fn();
            }
            $elapsedNs = hrtime(true) - $start;

            $avgMs   = ($elapsedNs / $iterations) / 1_000_000;
            $peakMb  = (memory_get_peak_usage(true) - $memStart) / 1_048_576;

            return [
                'label'  => $label,
                'avg_ms' => number_format($avgMs, 6),
                'peak_mb' => number_format(max($peakMb, 0), 3),
                'status' => 'ok',
            ];
        } catch (Throwable $e) {
            return [
                'label'  => $label,
                'avg_ms' => '—',
                'peak_mb' => '—',
                'status' => 'skipped: ' . $e->getMessage(),
            ];
        }
    }

    private function printTable(array $rows): void
    {
        CLI::newLine();
        CLI::table(
            array_map(static fn ($r) => [
                $r['label'],
                $r['avg_ms'],
                $r['peak_mb'],
                $r['status'],
            ], $rows),
            ['Function', 'Avg time (ms)', 'Peak mem (MB)', 'Status']
        );
    }
}
