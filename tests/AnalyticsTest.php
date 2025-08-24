<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../afiliados/Service/Analytics.php';

class AnalyticsTest extends TestCase
{
    protected $app;
    protected $analytics;

    public function __construct()
    {
        parent::__construct();
        $this->app = new MockApp();
        
        // Create a testable version of Analytics
        $this->analytics = new class($this->app) extends \hardMOB\Afiliados\Service\Analytics {
            public function __construct($app)
            {
                parent::__construct($app);
            }

            // Make protected methods public for testing
            public function testGetClientId($userId = null)
            {
                return $this->getClientId($userId);
            }

            public function testSendToGoogleAnalytics($storeId, $slug, $userId = null)
            {
                return $this->sendToGoogleAnalytics($storeId, $slug, $userId);
            }

            public function testGetPeriodStartDate($period)
            {
                return $this->getPeriodStartDate($period);
            }

            public function testSendGARequest($data)
            {
                return $this->sendGARequest($data);
            }
        };
    }

    public function testTrackClick()
    {
        $storeId = 1;
        $slug = 'B08N5WRWNW';
        $userId = 123;
        
        $click = $this->analytics->trackClick($storeId, $slug, $userId);
        
        $this->assertTrue($click !== null, 'Track click returns a click object');
        $this->assertEquals($storeId, $click->store_id, 'Click object has correct store_id');
        $this->assertEquals($slug, $click->slug, 'Click object has correct slug');
        $this->assertEquals($userId, $click->user_id, 'Click object has correct user_id');
    }

    public function testTrackClickWithoutUserId()
    {
        $storeId = 1;
        $slug = 'test-product';
        
        $click = $this->analytics->trackClick($storeId, $slug);
        
        $this->assertTrue($click !== null, 'Track click without user ID works');
        $this->assertEquals(0, $click->user_id, 'Click without user ID defaults to 0');
    }

    public function testTrackClickWithGoogleAnalytics()
    {
        // Set GA tracking ID
        $this->app->setOption('hardmob_afiliados_ga_tracking_id', 'UA-123456-1');
        
        $click = $this->analytics->trackClick(1, 'test-slug', 456);
        
        $this->assertTrue($click !== null, 'Track click with GA enabled works');
        // GA sending is tested indirectly through the mock
    }

    public function testGetClientIdWithUser()
    {
        $clientId = $this->analytics->testGetClientId(123);
        
        $this->assertIsString($clientId, 'Client ID is a string');
        $this->assertStringContains('user_', $clientId, 'User client ID contains user prefix');
    }

    public function testGetClientIdWithoutUser()
    {
        $clientId = $this->analytics->testGetClientId();
        
        $this->assertIsString($clientId, 'Anonymous client ID is a string');
        $this->assertEquals(32, strlen($clientId), 'Anonymous client ID is MD5 hash length');
    }

    public function testSendToGoogleAnalytics()
    {
        $this->app->setOption('hardmob_afiliados_ga_tracking_id', 'UA-123456-1');
        
        // This should not throw an exception
        $this->analytics->testSendToGoogleAnalytics(1, 'test-slug', 123);
        
        $this->assertTrue(true, 'Send to Google Analytics completes without error');
    }

    public function testSendToGoogleAnalyticsWithoutTrackingId()
    {
        $this->app->setOption('hardmob_afiliados_ga_tracking_id', '');
        
        // Should return early when no tracking ID
        $this->analytics->testSendToGoogleAnalytics(1, 'test-slug', 123);
        
        $this->assertTrue(true, 'Send to GA without tracking ID returns early');
    }

    public function testGetConversionRate()
    {
        $conversionData = $this->analytics->getConversionRate();
        
        $this->assertIsArray($conversionData, 'Conversion rate returns array');
        $this->assertArrayHasKey('total_clicks', $conversionData, 'Conversion data has total_clicks');
        $this->assertArrayHasKey('estimated_conversions', $conversionData, 'Conversion data has estimated_conversions');
        $this->assertArrayHasKey('conversion_rate', $conversionData, 'Conversion data has conversion_rate');
        
        $this->assertIsNumeric($conversionData['total_clicks'], 'Total clicks is numeric');
        $this->assertIsNumeric($conversionData['estimated_conversions'], 'Estimated conversions is numeric');
        $this->assertIsString($conversionData['conversion_rate'], 'Conversion rate is string percentage');
    }

    public function testGetConversionRateWithStoreFilter()
    {
        $storeId = 1;
        $conversionData = $this->analytics->getConversionRate($storeId);
        
        $this->assertIsArray($conversionData, 'Conversion rate with store filter returns array');
        $this->assertTrue($conversionData['total_clicks'] >= 0, 'Total clicks is non-negative');
    }

    public function testGetConversionRateWithPeriodFilter()
    {
        $periods = ['day', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            $conversionData = $this->analytics->getConversionRate(null, $period);
            
            $this->assertIsArray($conversionData, "Conversion rate for period '$period' returns array");
            $this->assertArrayHasKey('total_clicks', $conversionData, "Period '$period' has total_clicks");
        }
    }

    public function testGetPeriodStartDate()
    {
        $currentTime = time();
        
        $dayStart = $this->analytics->testGetPeriodStartDate('day');
        $weekStart = $this->analytics->testGetPeriodStartDate('week');
        $monthStart = $this->analytics->testGetPeriodStartDate('month');
        $yearStart = $this->analytics->testGetPeriodStartDate('year');
        
        $this->assertTrue($dayStart < $currentTime, 'Day start is in the past');
        $this->assertTrue($weekStart < $dayStart, 'Week start is before day start');
        $this->assertTrue($monthStart < $weekStart, 'Month start is before week start');
        $this->assertTrue($yearStart < $monthStart, 'Year start is before month start');
        
        // Test unknown period defaults to month
        $unknownStart = $this->analytics->testGetPeriodStartDate('unknown');
        $this->assertEquals($monthStart, $unknownStart, 'Unknown period defaults to month');
    }

    public function testSendGARequest()
    {
        $testData = [
            'v' => '1',
            'tid' => 'UA-123456-1',
            't' => 'event',
            'ec' => 'Test',
            'ea' => 'Click'
        ];
        
        // Should not throw an exception
        $this->analytics->testSendGARequest($testData);
        
        $this->assertTrue(true, 'Send GA request completes without error');
    }

    public function testTrackMultipleClicks()
    {
        $clicks = [];
        
        // Track multiple clicks
        for ($i = 1; $i <= 5; $i++) {
            $click = $this->analytics->trackClick($i, "slug-$i", $i * 10);
            $clicks[] = $click;
        }
        
        $this->assertEquals(5, count($clicks), 'Tracked 5 clicks successfully');
        
        foreach ($clicks as $index => $click) {
            $expectedStoreId = $index + 1;
            $this->assertEquals($expectedStoreId, $click->store_id, "Click $index has correct store_id");
        }
    }

    public function testConversionRateCalculation()
    {
        // Test that conversion rate calculation is consistent
        $conversionData1 = $this->analytics->getConversionRate();
        $conversionData2 = $this->analytics->getConversionRate();
        
        $this->assertEquals(
            $conversionData1['conversion_rate'], 
            $conversionData2['conversion_rate'], 
            'Conversion rate calculation is consistent'
        );
    }

    public function testAnalyticsWithInvalidStoreId()
    {
        // Test tracking with non-existent store
        $click = $this->analytics->trackClick(999, 'test-slug');
        
        $this->assertTrue($click !== null, 'Tracking with invalid store ID still creates click record');
        $this->assertEquals(999, $click->store_id, 'Invalid store ID is recorded as-is');
    }

    public function testAnalyticsWithSpecialCharacters()
    {
        // Test with slugs containing special characters
        $specialSlugs = [
            'slug-with-dashes',
            'slug_with_underscores',
            'slug with spaces',
            'slug/with/slashes',
            'slug?with=query&params=1'
        ];
        
        foreach ($specialSlugs as $slug) {
            $click = $this->analytics->trackClick(1, $slug);
            $this->assertEquals($slug, $click->slug, "Special character slug tracked: $slug");
        }
    }
}