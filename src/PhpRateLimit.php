<?php declare(strict_types=1);

namespace Hsalem7\PhpRateLimit;

use Hsalem7\PhpRateLimit\Stores\StoreInterface;

class PhpRateLimit
{
    /** @var StoreInterface */
    private $store;

    /** @var Clock */
    private $clock;

    public function __construct(StoreInterface $store, Clock $clock = null)
    {
        $this->store = $store;
        $this->clock = $clock ?? new Clock();
    }

    public function attempt(string $key, int $countLimit, $timeLimit): bool
    {
        if (!$this->withinLimitTimeline($key, $timeLimit)) {
            $this->resetTimeAndCount($key);
            return true;
        }

        if (!$this->exceededVisitLimit($key, $countLimit)) {
            $this->incrementVisits($key);
            return true;
        }

        return false;
    }

    public function getRemainingAttempts(string $key, int $countLimit, $timeLimit): int
    {
        if (!$this->withinLimitTimeline($key, $timeLimit)) {
            return $countLimit;
        }

        return $countLimit - $this->store->get("$key:count", 0);
    }

    private function withinLimitTimeline(string $key, int $timeLimit): bool
    {
        $time = $this->store->get("$key:time", 0);
        $timeDiff = $this->clock->currentTime() - $time;
        if ($timeDiff > $timeLimit) {
            return false;
        }

        return true;
    }

    private function exceededVisitLimit(string $key, int $countLimit): bool
    {
        $count = $this->store->get("$key:count", 0);

        return $count > $countLimit;
    }

    private function resetTimeAndCount(string $key): void
    {
        $this->store->set("$key:time", $this->clock->currentTime());
        $this->store->set("$key:count", 1);
    }

    private function incrementVisits(string $key)
    {
        $this->store->set(
            "$key:count",
            $this->store->get("$key:count", 0) + 1
        );
    }
}