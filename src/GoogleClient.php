<?php
/**
 * @license see LICENSE
 */

namespace Serps\SearchEngine\Google;

use Psr\Http\Message\RequestInterface;
use Serps\Core\Captcha\CaptchaSolverInterface;
use Serps\Core\Cookie\ArrayCookieJar;
use Serps\Core\Cookie\CookieJarInterface;
use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\Proxy;
use Serps\Core\UrlArchive;
use Serps\Exception;
use Serps\SearchEngine\Google\Exception\GoogleCaptchaException;
use Serps\SearchEngine\Google\GoogleClient\RequestBuilder;
use Serps\SearchEngine\Google\Page\GoogleCaptcha;
use Serps\SearchEngine\Google\Page\GoogleDom;
use Serps\SearchEngine\Google\Page\GoogleError;
use Serps\SearchEngine\Google\Page\GoogleSerp;
use Serps\SearchEngine\Google\GoogleUrl;
use Serps\SearchEngine\Google\GoogleUrlTrait;
use Zend\Diactoros\Request;

/**
 * Google client the handles google url routing, dom object constructions and request errors
 *
 * @property RequestBuilder $request
 */
class GoogleClient
{

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var CookieJarInterface
     */
    protected $cookieJar;

    protected $cookiesEnabled;

    protected $userAgent;

    /**
     * @var RequestBuilder|null
     */
    private $requestBuilder;

    /**
     * @param HttpClientInterface $client
     */
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->cookiesEnabled = false;
    }

    /**
     * @return RequestBuilder
     */
    public function getRequestBuilder()
    {
        if (null == $this->requestBuilder) {
            $this->requestBuilder = new RequestBuilder();
        }
        return $this->requestBuilder;
    }

    /**
     * Enable usage of cookies
     */
    public function enableCookies()
    {
        $this->cookiesEnabled = true;
    }

    /**
     * Disable usage of cookies
     */
    public function disableCookies()
    {
        $this->cookiesEnabled = false;
    }

    /**
     * @return CookieJarInterface
     */
    public function getCookieJar()
    {
        if (null == $this->cookieJar) {
            $this->cookieJar = new ArrayCookieJar();
        }
        return $this->cookieJar;
    }

    /**
     * @param CookieJarInterface $cookieJar
     */
    public function setCookieJar(CookieJarInterface $cookieJar)
    {
        $this->cookieJar = $cookieJar;
    }

    /**
     * @param GoogleUrlInterface $googleUrl
     * @param Proxy|null $proxy
     * @return GoogleSerp
     * @throws Exception
     * @throws Exception\PageNotFoundException
     * @throws Exception\RequestErrorException
     * @throws GoogleCaptchaException
     */
    public function query(GoogleUrlInterface $googleUrl, Proxy $proxy = null)
    {

        if ($googleUrl->getResultType() !== GoogleUrl::RESULT_TYPE_ALL) {
            throw new Exception(
                'The requested url is not valid for the google client.'
                . 'Google client only supports general searches. See GoogleUrl::setResultType() for more infos.'
            );
        }

        $cookieJar = $this->cookiesEnabled ? $this->getCookieJar() : null;


        $request = $this->getRequestBuilder()
                ->buildRequest($googleUrl);

        $response = $this->client->sendRequest($request, $proxy, $cookieJar);

        $statusCode = $response->getHttpResponseStatus();

        $effectiveUrl = GoogleUrlArchive::fromString($response->getEffectiveUrl()->__toString());

        if (200 == $statusCode) {
            return new GoogleSerp($response->getPageContent(), $effectiveUrl);
        } else {
            if (404 == $statusCode) {
                throw new Exception\PageNotFoundException();
            } else {
                $errorDom = new GoogleError($response->getPageContent(), $effectiveUrl);

                if ($errorDom->isCaptcha()) {
                    throw new GoogleCaptchaException(new GoogleCaptcha($errorDom));
                } else {
                    throw new Exception\RequestErrorException($errorDom);
                }
            }
        }

    }

    public function __get($name)
    {
        if ($name=='request') {
            return $this->getRequestBuilder();
        }
    }
}
