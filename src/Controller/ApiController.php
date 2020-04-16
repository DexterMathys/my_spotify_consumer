<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;

/**
 * Controller to work with Spotify API
 * @Route("/api/v1")
 */
class ApiController extends AbstractController {
    
    /**
      * @Route("/albums", name="albums")
      * Function that obtains the complete discography of a band passed by parameter
      */
    public function albums() {

        $base_uri_auth = $this->getParameter('base_uri_auth');
        $client_id = $this->getParameter('client_id');
        $client_secret = $this->getParameter('client_secret');

        $clientAuth = new Client(['base_uri' => $base_uri_auth]);
        $response = $clientAuth->request('POST', 'api/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($client_id.':'.$client_secret)
            ],
            'form_params' => [
                'grant_type' => 'client_credentials'
            ]
        ]);
        $dataAuth = json_decode($response->getBody()->getContents(), true);

        $request = Request::createFromGlobals();
        $artist = $request->query->get('q','');
        $result = array();
        
        if (trim($artist) !== '') {
            $base_uri_api = $this->getParameter('base_uri_api');
            $offset = 0;
            $limit = 50;
            $maxOffset = 2000 - $limit; // Maximum offset (including limit): 2,000.
            do {
                $client = new Client(['base_uri' => $base_uri_api]);
                $response = $client->request('GET', 'v1/search', [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => $dataAuth['token_type'] . ' ' . $dataAuth['access_token']
                    ],
                    'query' => [
                        'q' => "artist:$artist", // Required. Search query keywords
                        'type' => 'album', // Required. A comma-separated list of item types to search across. Valid types are: album , artist, playlist, track, show and episode.
                        'limit' => $limit, // Optional. Maximum number of results to return. Default: 20. Minimum: 1. Maximum: 50
                        'offset' => $offset // Optional. The index of the first result to return. Default: 0 (the first result). Maximum offset (including limit): 2,000.
                    ]
                ]);
        
                $data = json_decode($response->getBody()->getContents(), true);
                foreach ($data['albums']['items'] as $album) {
                    if (isset($album['images'][0])) {
                        $image = $album['images'][0];
                        $cover = array(
                            'height' => $image['height'],
                            'width' => $image['width'],
                            'url' => $image['url']
                        );
                    } else {
                        $cover = [];
                    }
                    
                    $result[] = array(
                        'name' => $album['name'],
                        'released' => $album['release_date'],
                        'tracks' => $album['total_tracks'],
                        'cover' => $cover
                    );
                }
                // if the item quantity is less than $limit or $offset is equal to maximum offset, then break the loop
                if (count($data['albums']['items']) < $limit || $offset == $maxOffset) {
                    $offset = -1;
                }else {
                    $offset += $limit;
                }
            } while ($offset >= 0);
        }
        
        // Convert to JSON
    	$dataJSON = $this->serializeJson($result);
    	$response = new Response($dataJSON);
    	$response->headers->set('Content-Type', 'application/json');
    	return $response;
    }

    /*
     * Function to convert $data to JSON
     */
    protected function serializeJson($data) {
        $encoders = array(new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
    	$serializer = new Serializer($normalizers, $encoders);
    	return $serializer->serialize($data, 'json');
    }

}