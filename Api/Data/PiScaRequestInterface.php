<?php
namespace Ebizmarts\SagePaySuite\Api\Data;

interface PiScaRequestInterface
{
    public const JS_ENABLED = 'javascript_enabled';
    public const ACCEPT_HEADERS = 'accept_headers';
    public const LANGUAGE = 'language';
    public const USER_AGENT = 'user_agent';

    /**
     * @return int
     */
    public function getJavascriptEnabled() : int;

    /**
     * Boolean that represents the ability of the cardholder browser to execute JavaScript.
     * @param int $enabled
     * @return void
     */
    public function setJavascriptEnabled(int $enabled) : void;

    /**
     * @return string
     */
    public function getAcceptHeaders() : string;

    /**
     * Exact content of the HTTP accept headers as sent to the 3DS Requestor from the Cardholder’s browser.
     * @param string $headers
     * @return void
     */
    public function setAcceptHeaders(string $headers) : void;

    /**
     * @return string
     */
    public function getLanguage() : string;

    /**
     * Value representing the browser language as defined in IETF BCP47. Returned from navigator.language property.
     * @param string $language
     * @return void
     */
    public function setLanguage(string $language) : void;

    /**
     * @return string
     */
    public function getUserAgent() : string;

    /**
     * Exact content of the HTTP user-agent header.
     * @param string $userAgent
     * @return void
     */
    public function setUserAgent(string $userAgent) : void;
}
