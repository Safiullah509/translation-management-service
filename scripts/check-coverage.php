<?php

$path = $argv[1] ?? null;
$threshold = (float) ($argv[2] ?? 95);

if (!$path || !is_file($path)) {
    fwrite(STDERR, "Coverage file not found.\n");
    exit(1);
}

$xml = simplexml_load_file($path);
if (!$xml) {
    fwrite(STDERR, "Unable to read coverage XML.\n");
    exit(1);
}

$metrics = $xml->project->metrics ?? null;
if (!$metrics) {
    fwrite(STDERR, "Coverage metrics not found.\n");
    exit(1);
}

$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if ($statements === 0) {
    fwrite(STDERR, "No statements recorded in coverage report.\n");
    exit(1);
}

$coverage = ($covered / $statements) * 100;
$coverageRounded = number_format($coverage, 2);

fwrite(STDOUT, "Statement coverage: {$coverageRounded}%\n");

if ($coverage + 1e-9 < $threshold) {
    fwrite(STDERR, "Coverage below {$threshold}% threshold.\n");
    exit(1);
}
