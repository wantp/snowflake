<?php

namespace wantp\Snowflake\Server;


interface CountServerInterFace
{
    public function getSequenceId($key);
}