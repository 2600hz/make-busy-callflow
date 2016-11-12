<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class RegistrationsTest extends DeviceTestCase {

    public function testMain() {
        $this->assertTrue(self::$register_device->getGateway()->register());
    }

}