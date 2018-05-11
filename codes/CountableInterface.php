<?php

namespace common\components\mailing\codes;

/**
 * Interface CountableInterface
 * @package common\components\mailing\codes
 */
interface CountableInterface
{
    /**
     * @return string[]
     */
    public function getCounters();

    /**
     * @param string $counter
     * @return int
     */
    public function getCount($counter);
}