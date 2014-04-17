<?php

namespace Phpf\Log\Driver;

interface LogDriverInterface
{

	public function log($message, $severity);

}
