<?php
namespace Ebizmarts\SagePaySuite\Api\Data;

interface PiScaRequestInterface
{
    const JS_ENABLED = 'javascript_enabled';
    const ACCEPT_HEADERS = 'accept_headers';
    const LANGUAGE = 'language';
    const USER_AGENT = 'user_agent';
    const JAVA_ENABLED = 'java_enabled';
    const COLOR_DEPTH = 'color_depth';
    const SCREEN_WIDTH = 'screen_width';
    const SCREEN_HEIGHT = 'screen_height';
    const TIMEZONE = 'timezone';

    /**
     * @return int
     */
    public function getJavascriptEnabled();

    /**
     * Boolean that represents the ability of the cardholder browser to execute JavaScript.
     * @param int $enabled
     * @return void
     */
    public function setJavascriptEnabled(int $enabled);

    /**
     * @return string
     */
    public function getAcceptHeaders();

    /**
     * Exact content of the HTTP accept headers as sent to the 3DS Requestor from the Cardholder’s browser.
     * @param string $headers
     * @return void
     */
    public function setAcceptHeaders(string $headers);

    /**
     * @return string
     */
    public function getLanguage();

    /**
     * Value representing the browser language as defined in IETF BCP47. Returned from navigator.language property.
     * @param string $language
     * @return void
     */
    public function setLanguage(string $language);

    /**
     * @return string
     */
    public function getUserAgent();

    /**
     * Exact content of the HTTP user-agent header.
     * @param string $userAgent
     * @return void
     */
    public function setUserAgent(string $userAgent);

    /**
     * @return int
     */
    public function getJavaEnabled();

    /**
     * Boolean that represents the ability of the cardholder browser to execute Java.
     * @param int $javaEnabled
     * @return void
     */
    public function setJavaEnabled(int $javaEnabled);

    /**
     * @return int
     */
    public function getColorDepth();

    /**
     * Exact content of the HTTP user-agent header.
     * @param int $colorDepth
     * @return void
     */
    public function setColorDepth(int $colorDepth);

    /**
     * @return int
     */
    public function getScreenWidth();

    /**
     * Exact content of the HTTP user-agent header.
     * @param int $screenWidth
     * @return void
     */
    public function setScreenWidth(int $screenWidth);

    /**
     * @return int
     */
    public function getScreenHeight();

    /**
     * Exact content of the HTTP user-agent header.
     * @param int $screenHeight
     * @return void
     */
    public function setScreenHeight(int $screenHeight);

    /**
     * @return int
     */
    public function getTimezone();

    /**
     * Exact content of the HTTP user-agent header.
     * @param int $timezone
     * @return void
     */
    public function setTimezone(int $timezone);
}
