<?php

namespace Gokure\Utils\Jobs\Concerns;

use Illuminate\Support\Facades\Log;
use Throwable;

trait Runnable
{
    /**
     * The maximum amount of times a job may be attempted, 0 is not limits.
     *
     * @var int
     */
    public $tries;

    /**
     * The maximum number of seconds a child worker may run, 0 is not limits.
     *
     * @var int
     */
    public $timeout;

    /**
     * If it set `true` will ignored when maximum amount of time a job has been attempted.
     *
     * @var bool
     */
    public $ignoreMaxAttemptsExceededException = false;

    /**
     * Set the tries.
     *
     * @param int $tries
     * @return $this
     */
    public function tries($tries)
    {
        $this->tries = $tries;

        return $this;
    }

    /**
     * Set the timeout.
     *
     * @param int $timeout
     * @return $this
     */
    public function timeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Ignore max attempts exceeded exception.
     *
     * @param bool $value
     * @return $this
     */
    public function ignoreMaxAttemptsExceededException($value = true)
    {
        $this->ignoreMaxAttemptsExceededException = (bool)$value;

        return $this;
    }

    /**
     * Re-tries after seconds when the job failed.
     *
     * @return int
     */
    public function retriesIn()
    {
        return 1;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Throwable
     */
    public function handle()
    {
        try {
            Log::info(sprintf('[Runnable] To handle the queued job: %s - %s', $this->job->resolveName(), $this->job->getRawBody()));
            $this->run();
        } catch (Throwable $ex) {
            if (!$this->job) {
                Log::error('[Runnable] no jobs found.');
                throw $ex;
            }

            $attempts = $this->attempts();
            $maxTries = $this->job->maxTries();

            try {
                $this->handleFailedJob($ex);
            } catch (Throwable $_) {
                // do nothing.
            }

            if ($maxTries && $attempts >= $maxTries) {
                Log::error(sprintf('[Runnable] The queued job has been attempted too many times: %s - %d - %s', $this->job->resolveName(), $attempts, $ex->getMessage()) . "\n" . $ex->getTraceAsString());

                try {
                    $this->handleFinalFailedJob($ex);
                } catch (Throwable $_) {
                    // do nothing.
                }

                // Ignore max attempts exceeded exception.
                if ($this->ignoreMaxAttemptsExceededException) {
                    $this->job->delete();
                    return;
                }
            }

            if (!$this->job->isDeleted() && !$this->job->isReleased() && !$this->job->hasFailed()) {
                $delay = (int)$this->retriesIn();
                $this->release($delay);
                Log::info(sprintf('[Runnable] The queued job %s will be released after %s second(s).',
                    $this->job->resolveName(),
                    $delay
                ));
            } else {
                throw $ex;
            }
        }
    }

    /**
     * 处理每次任务失败回调，注意在该方法中抛异常将会被忽略
     *
     * @param Throwable $e
     */
    protected function handleFailedJob(Throwable $e)
    {
        //
    }

    /**
     * 处理任务失败后最大尝试次数尝试回调，注意在该方法中抛异常将会被忽略
     *
     * @param Throwable $e
     */
    protected function handleFinalFailedJob(Throwable $e)
    {
        //
    }

    /**
     * Fire the job.
     *
     * @return void
     * @throws Throwable
     */
    abstract public function run();
}
