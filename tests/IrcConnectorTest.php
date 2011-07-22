<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once(
    dirname(__FILE__) .
    DIRECTORY_SEPARATOR . 'testenv' .
    DIRECTORY_SEPARATOR . 'bootstrap.php'
);

class   IrcConnectorTest
extends ErebotModuleTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->_serverConfig
            ->expects($this->any())
            ->method('getConnectionURI')
            ->will($this->returnValue(array('ircs://0.0.0.0/')));

        $this->_module = new Erebot_Module_IrcConnector(NULL);
        $this->_module->reload(
            $this->_connection,
            Erebot_Module_Base::RELOAD_ALL |
            Erebot_Module_Base::RELOAD_INIT
        );
    }

    public function tearDown()
    {
        unset($this->_module);
        parent::tearDown();
    }

    public function testRegistrationWithoutPassword()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->onConsecutiveCalls(
                '',         // no password
                'Erebot',   // nickname
                'identity', // identity
                'hostname', // hostname
                'realname'  // realname
            ));

        $event = new Erebot_Event_Logon($this->_connection);
        $this->_module->handleLogon($this->_eventHandler, $event);
        $this->assertEquals(2, count($this->_outputBuffer));
        $this->assertEquals(
            'NICK Erebot',
            $this->_outputBuffer[0]
        );
        $this->assertEquals(
            'USER identity hostname 0.0.0.0 :realname',
            $this->_outputBuffer[1]
        );
    }

    public function testRegistrationWithSomePassword()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->onConsecutiveCalls(
                'password', // password
                'Erebot',   // nickname
                'identity', // identity
                'hostname', // hostname
                'realname'  // realname
            ));

        $event = new Erebot_Event_Logon($this->_connection);
        $this->_module->handleLogon($this->_eventHandler, $event);
        $this->assertEquals(3, count($this->_outputBuffer));
        $this->assertEquals(
            'PASS password',
            $this->_outputBuffer[0]
        );
        $this->assertEquals(
            'NICK Erebot',
            $this->_outputBuffer[1]
        );
        $this->assertEquals(
            'USER identity hostname 0.0.0.0 :realname',
            $this->_outputBuffer[2]
        );
    }
}

