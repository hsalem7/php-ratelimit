<?php

use Hsalem7\PhpRateLimit\Clock;
use Hsalem7\PhpRateLimit\PhpRateLimit;
use Hsalem7\PhpRateLimit\Stores\StoreInterface;
use PHPUnit\Framework\TestCase;

class PhpRateLimitTests extends TestCase
{
    public function testAttemptWithTimeNotWithinTimeLimitWillReturnTrue()
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('get')->with("key:time")->willReturn(0);
        $clock = $this->createMock(Clock::class);
        $clock->method('currentTime')->willReturn(61);
        $sut = new PhpRateLimit($store, $clock);

        $this->assertTrue($sut->attempt('key', 1, 60));
    }

    public function testAttemptWithTimeNotWithinTimeLimitWillResetTimeAndCount()
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('get')->with("key:time")->willReturn(0);
        $clock = $this->createMock(Clock::class);
        $clock->method('currentTime')->willReturn(61);

        $store->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [$this->equalTo('key:time'), $this->equalTo(61)],
                [$this->equalTo('key:count'), $this->equalTo(1)]
            );

        $sut = new PhpRateLimit($store, $clock);
        $sut->attempt('key', 1, 60);
    }

    public function testAttemptWithTimeWithinTimeLimitAndCountLessThanCountLimitWillReturnTrue()
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('get')->willReturnCallback(function ($arg) {
            if ($arg == 'key:time') return 0;
            if ($arg == 'key:count') return 0;
            return null;
        });
        $clock = $this->createMock(Clock::class);
        $clock->method('currentTime')->willReturn(50);
        $sut = new PhpRateLimit($store, $clock);

        $this->assertTrue($sut->attempt('key', 1, 60));
    }

    public function testAttemptWithTimeWithinTimeLimitAndCountLessThanCountLimitWillIncrementCount()
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('get')->willReturnCallback(function ($arg) {
            if ($arg == 'key:time') return 0;
            if ($arg == 'key:count') return 0;
            return null;
        });
        $clock = $this->createMock(Clock::class);
        $clock->method('currentTime')->willReturn(50);

        $store->expects($this->once())->method('set')->with('key:count', 1);

        $sut = new PhpRateLimit($store, $clock);
        $sut->attempt('key', 1, 60);
    }

    public function testAttemptWithTimeWithinTimeLimitAndCountMoreThanCountLimitWillReturnFalse()
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('get')->willReturnCallback(function ($arg) {
            if ($arg == 'key:time') return 0;
            if ($arg == 'key:count') return 2;
        });
        $clock = $this->createMock(Clock::class);
        $clock->method('currentTime')->willReturn(50);
        $sut = new PhpRateLimit($store, $clock);

        $this->assertFalse($sut->attempt('key', 1, 60));
    }

    public function testGetRemainingAttemptsWithTimeNotWithinTimeLimitTimelineWillReturnTheCountLimit()
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('get')->willReturnCallback(function ($arg) {
            if ($arg == 'key:time') return 0;
            if ($arg == 'key:count') return 2;
        });
        $clock = $this->createMock(Clock::class);
        $clock->method('currentTime')->willReturn(100);
        $sut = new PhpRateLimit($store, $clock);

        $this->assertEquals(5, $sut->getRemainingAttempts('key', 5, 60));
    }

    public function testGetRemainingAttemptsWithTimeWithinTimeLimitTimelineWillReturnDiffBetweenCountLimitAndCount()
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('get')->willReturnCallback(function ($arg) {
            if ($arg == 'key:time') return 0;
            if ($arg == 'key:count') return 2;
        });
        $clock = $this->createMock(Clock::class);
        $clock->method('currentTime')->willReturn(50);
        $sut = new PhpRateLimit($store, $clock);

        $this->assertEquals(3, $sut->getRemainingAttempts('key', 5, 60));
    }
}