<?php

namespace KazooTests\Applications\Callflow;

use \StdClass;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Configuration;
use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\User;
use \MakeBusy\Kazoo\Applications\Crossbar\RingGroup;

use \MakeBusy\Common\Log;

class RingGroupTest extends CallflowTestCase
{

    private static $user = array();
    private static $device = array();

    private static $ring_group_1;
    private static $ring_group_2;
    private static $ring_group_3;
    private static $ring_group_4;

    const RG_EXT_1 = '8001';
    const RG_EXT_2 = '8002';
    const RG_EXT_3 = '8003';
    const RG_EXT_4 = '8004';
    const DURATION = '30';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        $test_account = self::getTestAccount();

        foreach (range('a','g') as $letter) {
            self::$user[$letter] = new User($test_account);
        }

        foreach (range('a','g') as $letter) {
            self::$device[$letter] = new Device($test_account, TRUE, ["owner_id" => self::$user[$letter]->getID()]);
        }

        self::$ring_group_1 = new RingGroup(
            $test_account,
            [self::RG_EXT_1],
            [
                [   "id" => self::$device['b']->getId(),
                  "type" => "device",
               "timeout" => "30",
                 "delay" => "0"
                ],
                [   "id" => self::$device['c']->getId(),
                  "type" => "device",
               "timeout" => "25",
                 "delay" => "5"
                ],
                [   "id" => self::$device['d']->getId(),
                  "type" => "device",
               "timeout" => "20",
                 "delay" => "10"
                ],
                [   "id" => self::$user['e']->getId(),
                  "type" => "user",
               "timeout" => "15",
                 "delay" => "15"
                ],
                [   "id" => self::$user['f']->getId(),
                  "type" => "user",
               "timeout" => "10",
                 "delay" => "20"
                ],
                [   "id" => self::$user['g']->getId(),
                  "type" => "user",
               "timeout" => "5",
                 "delay" => "25"
                ]
            ]
        );

        self::$ring_group_2 = new RingGroup(
            $test_account,
            [self::RG_EXT_2],
            [
                [   "id" => self::$device['b']->getId(),
                  "type" => "device",
               "timeout" => "10"
                ],
                [   "id" => self::$device['c']->getId(),
                  "type" => "device",
               "timeout" => "10"
                ]
            ]
        );

        self::$ring_group_3 = new RingGroup(
            $test_account,
            [self::RG_EXT_3],
            [
                [   "id" => self::$device['d']->getId(),
                  "type" => "device",
               "timeout" => "10"
                ],
                [   "id" => self::$device['e']->getId(),
                  "type" => "device",
               "timeout" => "10"
                ]
            ],
            "simultaneous"
        );

        self::$ring_group_4 = new RingGroup(
            $test_account,
            [self::RG_EXT_4],
            [
                [   "id" => self::$device['f']->getId(),
                  "type" => "device",
               "timeout" => "10"
                ],
                [   "id" => self::$device['g']->getId(),
                  "type" => "device",
               "timeout" => "10"
                ]
            ],
            "single"
        );

        Profiles::loadFromAccounts();
        Profiles::syncGateways();
    }

    public function setUp() {
        // NOTE: this hangs up all channels, we may not want
        //  to do this if we plan on executing multiple tests
        //  at once
        self::getEsl()->flushEvents();
        self::getEsl()->api("hupall");
    }

    /*
     * MKBUSY - 69
     * 1) Create a ring group with a mix of devices and user endpoints.
     * 2) On one endpoint, set the leg delay and on all endpoints set a leg timeout.
     * 3) Call the ring group and ensure the devices without a leg delay ring first as well as all devices only ring for their leg timeout.
     */

    public function testRingGroupTimeoutDelay() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$device['a']->getId();

        foreach (range('b', 'g') as $leg) {
            $username[$leg] = self::$device[$leg]->getSipUsername();
        }

        $uuid_base = "testRingGroupTimeoutDelay-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("trying SIP URI %s", $sip_uri);
            $target = self::RG_EXT_1 . '@' . $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);

            foreach (range('b', 'g') as $leg) {
                Log::debug("trying leg %s", $leg);
                $race[$leg] = $this->waitForInbound($username[$leg], 30);
            }

            foreach (range('b', 'g') as $leg) {
                Log::debug("trying leg %s", $leg);
                $destroy[$leg] = $race[$leg]->waitDestroy("30");
                $start[$leg] = $destroy[$leg]->getHeader('variable_start_epoch');
                $duration[$leg] = $destroy[$leg]->getHeader('variable_duration');
            }

            $start_time = $start['b'];
            $expected_duration = self::DURATION;

            foreach (range('b', 'g') as $leg) {
                Log::debug("trying leg %s", $leg);
                $dur_low  = $expected_duration - 2;
                $dur_high = $expected_duration + 2;

                $this->assertLessThanOrEqual($dur_high, $duration[$leg]);
                $this->assertgreaterthanorequal($dur_low, $duration[$leg]);

                $start_low  = $start_time - 2;
                $start_high = $start_time + 2;

                $this->assertLessThanOrEqual($start_high, $start[$leg]);
                $this->assertgreaterthanorequal($start_low, $start[$leg]);

                $expected_duration = $expected_duration - 5;
                $start_time = $start_time + 5;
            }
        }
    }

    /*
     * MKBUSY - 70
     * 1) Create a ring group callflow with two or more devices and no strategy property.
     * 2) Call the ring group and ensure all devices ring at the same time.
     * 3) Set the ring group callflow data with a strategy "simultaneous".
     * 4) Call the ring group and ensure all devices ring at the same time.
     * 5) Set the ring group callflow data with a strategy "single".
     * 6) Call the ring group and ensure each device rings one at a time in the order defined on the callflow.
     */

    public function testRingGroupStrategyNone() {
        Log::notice("%s", __METHOD__);
        $channels = self::getChannels();
        $a_device_id = self::$device['a']->getId();

        foreach (range('b', 'g') as $leg) {
            Log::debug("trying leg %s", $leg);
            $username[$leg] = self::$device[$leg]->getSipUsername();
        }

        $uuid_base = "testRingGroupStrategyNone-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("trying SIP URI %s", $sip_uri);
            $target = self::RG_EXT_2 . '@' . $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);

            foreach (range('b', 'c') as $leg) {
                Log::debug("trying leg %s", $leg);
                $race[$leg] = $this->waitForInbound($username[$leg], 10);
            }

            foreach (range('b', 'c') as $leg) {
                Log::debug("trying leg %s", $leg);
                $destroy[$leg] = $race[$leg]->waitDestroy("21");
                $start[$leg] = $destroy[$leg]->getHeader('variable_start_epoch');
            }

            $this->assertEquals($start['b'], $start['c']);
        }
    }

    public function testRingGroupStrategySimultaneous() {
        Log::notice("%s", __METHOD__);
        $channels = self::getChannels();
        $a_device_id = self::$device['a']->getId();

        foreach (range('d', 'e') as $leg) {
            Log::debug("trying leg %s", $leg);
            $username[$leg] = self::$device[$leg]->getSipUsername();
        }

        $uuid_base = "testRingGroupStrategySimultaneous-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("trying SIP URI %s", $sip_uri);
            $target = self::RG_EXT_3 . '@' . $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);

            foreach (range('d', 'e') as $leg) {
                Log::debug("trying leg %s", $leg);
                $race[$leg] = $this->waitForInbound($username[$leg], 10);
            }

            foreach (range('d', 'e') as $leg) {
                Log::debug("trying leg %s", $leg);
                $destroy[$leg] = $race[$leg]->waitDestroy("21");
                $start[$leg] = $destroy[$leg]->getHeader('variable_start_epoch');
            }

            $this->assertEquals($start['d'], $start['e']);

        }
    }

    public function testRingGroupStrategySingle() {
        Log::notice("%s", __METHOD__);
        $channels = self::getChannels();
        $a_device_id = self::$device['a']->getId();

        foreach (range('f', 'g') as $leg) {
            Log::debug("trying leg %s", $leg);
            $username[$leg] = self::$device[$leg]->getSipUsername();
        }

        $uuid_base = "testRingGroupStrategySingle-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("trying SIP URI %s", $sip_uri);
            $target = self::RG_EXT_4 . '@' . $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);

            foreach (range('f', 'g') as $leg) {
                Log::debug("trying leg %s", $leg);
                $race[$leg] = $this->waitForInbound($username[$leg], 21);
            }

            foreach (range('f', 'g') as $leg) {
                Log::debug("trying leg %s", $leg);
                $destroy[$leg] = $race[$leg]->waitDestroy("21");
                $start[$leg] = $destroy[$leg]->getHeader('variable_start_epoch');
                $end[$leg] = $destroy[$leg]->getHeader('variable_end_epoch');
            }

            $this->assertEquals($end['f'], $start['g']);
        }
    }

}

