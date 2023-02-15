<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'disciple-tools-email/disciple-tools-email.php' );

        $this->assertContains(
            'disciple-tools-email/disciple-tools-email.php',
            get_option( 'active_plugins' )
        );
    }
}
