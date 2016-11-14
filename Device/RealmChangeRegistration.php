<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class RealmChangeRegistration extends DeviceTestCase {

    public function tearDown() {
        self::getTestAccount()->setAccountRealm(self::$realm);
        self::$a_device->getGateway()->setParam('realm', self::$realm);
        self::$a_device->getGateway()->setParam('from-domain', self::$realm);

        self::$a_device->getGateway()->kill();
        self::getProfile("auth")->rescan(); 
    }

    public function testMain() {
        $this->markTestIncomplete('Known issue: see comments');
        self::getTestAccount()->setAccountRealm('blah.com');

        self::$a_device->getGateway()->kill();
        self::getProfile("auth")->rescan();

        $this->assertFalse( self::$a_device->getGateway()->register() );

        // TODO: this should fail: it sets only in-memory gateway parameters (no way to pass it to freeswitch as it gets gateways from kazoo only)
        self::$a_device->getGateway()->setParam('realm', 'blah.com');
        self::$a_device->getGateway()->setParam('from-domain', 'blah.com');

        self::$a_device->getGateway()->kill();
        self::getProfile("auth")->rescan(); 

        $this->assertTrue( self::$a_device->getGateway()->register() );
    }

}