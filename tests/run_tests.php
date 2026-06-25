<?php

/**
 * Test Runner for hardMOB Afiliados
 * Runs all test suites and reports results
 */

require_once __DIR__ . '/bootstrap.php';

// Include all test files
require_once __DIR__ . '/AffiliateGeneratorTest.php';
require_once __DIR__ . '/LinkCacheTest.php';
require_once __DIR__ . '/AnalyticsTest.php';
require_once __DIR__ . '/LinkParserTest.php';
require_once __DIR__ . '/ConnectorTest.php';

echo "=====================================================\n";
echo "hardMOB Afiliados - Automated Test Suite\n";
echo "=====================================================\n";
echo "Testing main functionalities:\n";
echo "- Affiliate link generation\n";
echo "- Cache system (file/Redis drivers)\n";
echo "- Click tracking and analytics\n";
echo "- Input validation\n";
echo "- Store connector validation\n";
echo "=====================================================\n";

$testClasses = [
    'AffiliateGeneratorTest',
    'LinkCacheTest', 
    'AnalyticsTest',
    'LinkParserTest',
    'ConnectorTest'
];

$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;
$results = [];

foreach ($testClasses as $testClass) {
    echo "\n🔍 Starting $testClass...\n";
    
    $test = new $testClass();
    $passed = $test->run();
    
    $results[$testClass] = [
        'passed' => $passed,
        'assertions' => $test->assertions ?? 0,
        'passed_count' => $test->passed ?? 0,
        'failed_count' => $test->failed ?? 0
    ];
    
    $totalTests += $results[$testClass]['assertions'];
    $totalPassed += $results[$testClass]['passed_count'];
    $totalFailed += $results[$testClass]['failed_count'];
    
    echo ($passed ? "✅ PASSED" : "❌ FAILED") . "\n";
}

echo "\n=====================================================\n";
echo "📊 FINAL RESULTS SUMMARY\n";
echo "=====================================================\n";

foreach ($results as $testClass => $result) {
    $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
    echo sprintf("%-25s %s (%d/%d assertions)\n", 
        $testClass, 
        $status, 
        $result['passed_count'], 
        $result['assertions']
    );
}

echo "\n-----------------------------------------------------\n";
echo "Total Test Suites: " . count($testClasses) . "\n";
echo "Total Assertions:  $totalTests\n";
echo "Passed:           $totalPassed\n";
echo "Failed:           $totalFailed\n";

$successRate = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 2) : 0;
echo "Success Rate:     {$successRate}%\n";

if ($totalFailed === 0) {
    echo "\n🎉 ALL TESTS PASSED! 🎉\n";
    echo "The affiliate system is working correctly.\n";
    exit(0);
} else {
    echo "\n⚠️  SOME TESTS FAILED ⚠️\n";
    echo "Please review the failed assertions above.\n";
    exit(1);
}