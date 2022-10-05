<?php

namespace App\Helpers\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use stdClass;

abstract class Api
{
    protected $client;

    abstract protected function getBaseUri(): string;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->getBaseUri()
        ]);
    }

    /**
     * Perform a GET request
     *
     * @param $path
     * @return mixed
     */
    public function get($path)
    {
        $exception = null;

        try {
            Log::debug($path);
            $response = $this->client->get($path);
            $content = $response->getBody()->getContents();
            Log::debug($content);
            return json_decode($content);
        } catch (ConnectException $e) {
            $exception = 'ConnectException';
        } catch (ClientException $e) {
            $exception = 'ClientException';
        } catch (ServerException $e) {
            $exception = 'ServerException';
        } catch (RequestException $e) {
            $exception = 'RequestException';
        } catch (\Exception $e) {
            $exception = 'GeneralException';
        }

        if ($exception) {
            Log::error(sprintf(
                'API error (%s): %s, class: %s, path: %s.',
                $exception,
                $e->getMessage(),
                get_class($this),
                $path
            ));
        }

        return NULL;
    }
}
