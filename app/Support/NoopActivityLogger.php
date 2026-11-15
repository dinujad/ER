<?php

namespace App\Support;

/**
 * Drop-in stub for Spatie-style activity() fluent API used by the Accounting module.
 */
class NoopActivityLogger
{
    public function on(mixed $subject): self
    {
        return $this;
    }

    public function performedOn(mixed $subject): self
    {
        return $this;
    }

    public function causedBy(mixed $causer): self
    {
        return $this;
    }

    public function withProperties(array $properties): self
    {
        return $this;
    }

    public function useLog(string $logName): self
    {
        return $this;
    }

    public function log(string $description): self
    {
        return $this;
    }
}
