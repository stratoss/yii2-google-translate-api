<?php
/**
 * @copyright Copyright &copy; Roman Bahatyi, richweber.net, 2015
 * @copyright Copyright &copy; Stanimir Stoyanov, 2016
 * @package yii2-google-translate-api
 * @version 1.1.3
 */

namespace stratoss\google\translate;

use yii\helpers\Json;

/**
 * Yii2 extension for Google Translate API
 *
 * @link https://cloud.google.com/translate/v2/using_rest
 * @author Roman Bahatyi <rbagatyi@gmail.com>
 * @since 1.0
 * @author Stanimir Stoyanov <stanimir@datacentrix.org>
 * @since 1.1
 */
class Translation
{
    /**
     * API key
     * @var string
     */
    public $key;

    /**
     * API URL
     */
    const API_URL = 'https://www.googleapis.com/language/translate/v2';

    /**
     * You can translate text from one language
     * to another language
     * @param string $source Source language
     * @param string $target Target language
     * @param string|array $text   Source text string/array of strings
     * @throws \Exception if the translated text is neither string or array
     * @return array
     */
    public function translate($source, $target, $text)
    {
        if (gettype($text) !== 'string' && gettype($text) !== 'array') {
            throw new \Exception("The translated text must be either string or array");
        }
        return $this->getResponse($this->getRequest('', $text, $source, $target));
    }

    /**
     * You can discover the supported languages of this API
     * @return array Array supported languages
     */
    public function discover()
    {
        return $this->getResponse($this->getRequest('languages'));
    }

    /**
     * You can detect the language of a text string
     * @param  string $text Source text string
     * @return array        Data properties
     */
    public function detect($text)
    {
        return $this->getResponse($this->getRequest('detect', $text));
    }

    /**
     * Forming query parameters
     * @param  string $method API method
     * @param  string|array $text   Source text string
     * @param  string $source Source language
     * @param  string $target Target language
     * @return array          Data properties
     * @see https://cloud.google.com/translate/v2/using_rest#Translate for multiple translations
     */
    protected function getRequest($method, $text = '', $source = '', $target = '')
    {
        if (gettype($text) === 'string') {
            $text = [$text];
        }

        foreach ($text as $textKey => $textValue) {
            $text[$textKey] = rawurlencode($textValue);
        }

        $request = self::API_URL . "/{$method}?key={$this->key}&source={$source}&target=$target";

        foreach ($text as $string) {
            $request .= "&q={$string}";
        }
        return $request;
    }

    /**
     * Getting response
     * @param string $request
     * @return array
     * @throws \Exception
     */
    protected function getResponse($request)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
			$googleResponse = Json::decode($response);
			if (array_key_exists('error', $googleResponse)) {
				$message = $googleResponse['error']['errors'][0]['domain'] . ', ';
				$message .= $googleResponse['error']['errors'][0]['reason'] . ', ';
				$message .= $googleResponse['error']['errors'][0]['message'];
			} else {
				$message = 'Invalid response from Google!';
			}
			throw new \Exception($message);
		}
        curl_close($ch);
        //$response = file_get_contents($request);
        return Json::decode($response, true);
    }
}
