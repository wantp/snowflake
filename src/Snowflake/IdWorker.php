<?php

namespace wantp\Snowflake;

use wantp\Snowflake\Server\CountServerInterFace;
use wantp\Snowflake\Server\FileCountServer;
use wantp\Snowflake\Server\RedisCountServer;

/**
 * Class IdWorker snowflake算法生成unique id
 * @package Snowflake
 */
class IdWorker
{
    const EPOCH_OFFSET = 1293811200000;
    const SIGN_BITS = 1;
    const TIMESTAMP_BITS = 41;
    const DATA_CENTER_BITS = 5;
    const MACHINE_ID_BITS = 5;
    const SEQUENCE_BITS = 12;

    const FILE_COUNT_SERVICE = 1;
    const REDIS_COUNT_SERVICE = 2;

    /**
     * @var IdWorker
     */
    private static $idWorker;
    /**
     * @var mixed
     */
    protected $dataCenterId;

    /**
     * @var mixed
     */
    protected $machineId;

    /**
     * @var null|int
     */
    protected $lastTimestamp = null;

    /**
     * @var null|CountServerInterFace
     */
    private $countService = null;

    /**
     * @var int
     */
    protected $sequence = 1;
    protected $signLeftShift = self::TIMESTAMP_BITS + self::DATA_CENTER_BITS + self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
    protected $timestampLeftShift = self::DATA_CENTER_BITS + self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
    protected $dataCenterLeftShift = self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
    protected $machineLeftShift = self::SEQUENCE_BITS;
    protected $maxSequenceId = -1 ^ (-1 << self::SEQUENCE_BITS);
    protected $maxMachineId = -1 ^ (-1 << self::MACHINE_ID_BITS);
    protected $maxDataCenterId = -1 ^ (-1 << self::DATA_CENTER_BITS);

    /**
     * UniqueId constructor.
     * @param $dataCenter_id
     * @param $machine_id
     * @throws \Exception
     */
    private function __construct($dataCenter_id, $machine_id)
    {
        if ($dataCenter_id > $this->maxDataCenterId) {
            throw new \Exception('data center id should between 0 and ' . $this->maxDataCenterId);
        }
        if ($machine_id > $this->maxMachineId) {
            throw new \Exception('machine id should between 0 and ' . $this->maxMachineId);
        }
        $this->dataCenterId = $dataCenter_id;
        $this->machineId = $machine_id;
        $this->countService = new FileCountServer();
    }

    /**
     * Notes:__clone
     * @author  zhangrongwang
     * @date 2018-12-25 11:42:06
     */
    private function __clone()
    {

    }

    /**
     * Notes:getIns
     * @author  zhangrongwang
     * @date 2018-12-25 11:42:06
     * @param int $dataCenterId
     * @param int $machineId
     * @throws \Exception
     * @return IdWorker $snowflake;
     */
    public static function getIns($dataCenterId = 0, $machineId = 0)
    {
        if (!(self::$idWorker instanceof self)) {
            self::$idWorker = new self($dataCenterId, $machineId);
        }
        return self::$idWorker;
    }

    /**
     * Notes:setFileCountServer
     * @author  zhangrongwang
     * @date 2018-12-26 10:40:58
     * @return IdWorker
     */
    public function setFileCountServer()
    {
        $this->countService = new FileCountServer();
        return $this;
    }

    /**
     * Notes:setRedisCountServer
     * @author  zhangrongwang
     * @date 2018-12-26 10:41:02
     * @param $config ['host'=>'','port'=>'','dbIndex'=>'','auth'=>'']
     * @throws \Exception
     * @return IdWorker
     */
    public function setRedisCountServer($config)
    {
        $this->countService = new RedisCountServer($config);
        return $this;
    }

    /**
     * Notes:id
     * @author  zhangrongwang
     * @date 2018-12-25 11:48:09
     * @throws \Exception
     */
    public function id()
    {
        $sign = 0;
        $timestamp = $this->getUnixTimestamp();
        if ($timestamp < $this->lastTimestamp) {
            throw new \Exception('Clock moved backwards!');
        }
        $countServiceKey = $this->dataCenterId . '-' . $this->machineId . '-' . $timestamp;
        $sequence = $this->countService->getSequenceId($countServiceKey);
        if ($sequence > $this->maxSequenceId) {
            $timestamp = $this->getUnixTimestamp();
            while ($timestamp <= $this->lastTimestamp) {
                $timestamp = $this->getUnixTimestamp();
            }
            $countServiceKey = $this->dataCenterId . '-' . $this->machineId . '-' . $timestamp;
            $sequence = $this->countService->getSequenceId($countServiceKey);
        }
        $this->lastTimestamp = $timestamp;
        $time = (int)($timestamp - self::EPOCH_OFFSET);
        $id = ($sign << $this->signLeftShift) | ($time << $this->timestampLeftShift) | ($this->dataCenterId << $this->dataCenterLeftShift) | ($this->machineId << $this->machineLeftShift) | $sequence;
        return (string)$id;
    }

    /**
     * Notes:parse
     * @author  zhangrongwang
     * @date 2018-12-25 16:41:27
     * @param $uuid
     * @return array
     */
    public function parse($uuid)
    {
        $binUuid = decbin($uuid);
        $len = strlen($binUuid);
        $sequenceStart = $len - self::SEQUENCE_BITS;
        $sequence = substr($binUuid, $sequenceStart, self::SEQUENCE_BITS);
        $machineIdStart = $len - self::MACHINE_ID_BITS - self::SEQUENCE_BITS;
        $machineId = substr($binUuid, $machineIdStart, self::MACHINE_ID_BITS);
        $dataCenterIdStart = $len - self::DATA_CENTER_BITS - self::MACHINE_ID_BITS - self::SEQUENCE_BITS;
        $dataCenterId = substr($binUuid, $dataCenterIdStart, self::DATA_CENTER_BITS);
        $timestamp = substr($binUuid, 0, $dataCenterIdStart);
        $realTimestamp = bindec($timestamp) + self::EPOCH_OFFSET;
        $timestamp = substr($realTimestamp, 0, -3);
        $microSecond = substr($realTimestamp, -3);
        return [
            'timestamp' => date('Y-m-d H:i:s', $timestamp) . '.' . $microSecond,
            'dataCenterId' => bindec($dataCenterId),
            'machineId' => bindec($machineId),
            'sequence' => bindec($sequence),
        ];
    }

    /**
     * Notes:getUnixTimestamp
     * @author  zhangrongwang
     * @date 2018-12-25 11:42:06
     */
    private function getUnixTimestamp()
    {
        return floor(microtime(true) * 1000);
    }
}