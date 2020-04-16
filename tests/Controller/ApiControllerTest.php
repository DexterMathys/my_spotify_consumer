<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
    }
    
    public function testAlbumsURL()
    {
        // Test URL
        $this->client->request('GET', '/api/v1/albums');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAlbumsURLWhitParam()
    {
        // Test URL with param q
        $this->client->request('GET', '/api/v1/albums', array(
            'q' => "ac-dc"
        ));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAlbumsHeaders()
    {
        // Test headers
        $this->client->request('GET', '/api/v1/albums', array(
            'q' => "ac-dc"
        ));
        $this->assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
    }

    public function testAlbumsResponseContent()
    {
        // Test response content
        $this->client->request('GET', '/api/v1/albums');
        $this->assertStringContainsString('[]', $this->client->getResponse()->getContent());
    }
}
?>