<?php

namespace wantp\Snowflake\Server;


class FileCountServer implements CountServerInterFace
{
    /**
     * Notes:getSequenceId
     * @author  zhangrongwang
     * @date 2018-12-26 10:44:21
     * @param $key
     * @return int
     */
    public function getSequenceId($key)
    {
        $sequenceId = 0;
        $lastTimestamp = 0;
        $lastSequenceId = 0;
        $keyFields = explode('-', $key);
        $timestamp = (int)end($keyFields);

        $lockFileName = __DIR__ . DIRECTORY_SEPARATOR . 'sequenceId.lock';

        if (file_exists($lockFileName)) {
            $fp = fopen($lockFileName, 'r');
            $lastTimestamp = (int)fgets($fp);
            $lastSequenceId = (int)fgets($fp);
            fclose($fp);
        }

        $fp = fopen($lockFileName, 'w');
        $keyFields = explode('-', $key);
        $timestamp = end($keyFields);
        if (flock($fp, LOCK_EX)) {
            if ($timestamp == $lastTimestamp) {
                $sequenceId = $lastSequenceId + 1;
            }
            fwrite($fp, $timestamp . PHP_EOL . $sequenceId . PHP_EOL);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return $sequenceId;
    }
}