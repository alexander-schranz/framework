<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Components\Http\Cookies\Cookie;
use Spiral\Components\Http\MiddlewareInterface;
use Spiral\Components\Http\Response;
use Spiral\Core\Component;
use Spiral\Core\Dispatcher\ClientException;
use Spiral\Helpers\StringHelper;

class CsrfFilter implements MiddlewareInterface
{
    /**
     * Token have to check in cookies and queries.
     */
    const COOKIE = 'csrf-token';

    /**
     * Verification cookie lifetime.
     */
    const LIFETIME = 86400;

    /**
     * Header to check for token instead of POST/GET data.
     */
    const HEADER = 'X-CSRF-Token';

    /**
     * Parameter name used to represent client token in POST data.
     */
    const PARAMETER = 'csrf-token';

    /**
     * Handle request generate response. Middleware used to alter incoming Request and/or Response
     * generated by inner pipeline layers.
     *
     * @param ServerRequestInterface $request Server request instance.
     * @param \Closure               $next    Next middleware/target.
     * @return ResponseInterface
     * @throws ClientException
     */
    public function __invoke(ServerRequestInterface $request, \Closure $next = null)
    {
        $token = null;
        $setCookie = false;

        $cookies = $request->getCookieParams();
        if (isset($cookies[self::COOKIE]))
        {
            $token = $cookies[self::COOKIE];
        }
        else
        {
            //Making new token
            $token = StringHelper::random(16);
            $setCookie = true;
        }

        if ($this->isRequired($request))
        {
            if (!$this->compare($token, $this->fetchToken($request)))
            {
                //Let's return response directly
                return (new Response('', 412))->withStatus(412, 'Bad CSRF Token');
            }
        }

        $response = $next($request->withAttribute('crsfToken', $token));
        if ($setCookie && $response instanceof ResponseInterface)
        {
            //Will work even with non spiral responses
            $response = $response->withAddedHeader(
                'Set-Cookie',
                Cookie::create(
                    self::COOKIE,
                    $token,
                    self::LIFETIME,
                    null,
                    $request->getAttribute('cookieDomain', null)
                )->packHeader()
            );
        }

        return $response;
    }

    /**
     * Check if middleware should check csrf token.
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isRequired(ServerRequestInterface $request)
    {
        return !in_array($request->getMethod(), array('GET', 'HEAD', 'OPTIONS'));
    }

    /**
     * Fetch token from request.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function fetchToken(ServerRequestInterface $request)
    {
        if ($request->hasHeader(self::HEADER))
        {
            return (string)$request->getHeaderLine(self::HEADER);
        }

        //Checking POST data
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody[self::PARAMETER]))
        {
            if (is_string($parsedBody[self::PARAMETER]))
            {
                return (string)$parsedBody[self::PARAMETER];
            }
        }

        return '';
    }

    /**
     * Perform timing attack safe string comparison of tokens.
     *
     * @link http://blog.ircmaxell.com/2014/11/its-all-about-time.html
     * @param string $token Known token.
     * @param string $clientToken
     * @return bool
     */
    protected function compare($token, $clientToken)
    {
        if (function_exists('hash_compare'))
        {
            return hash_compare($token, $clientToken);
        }

        $tokenLength = strlen($token);
        $clientLength = strlen($clientToken);

        if ($clientLength != $tokenLength)
        {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $clientLength; $i++)
        {
            $result |= (ord($token[$i]) ^ ord($clientToken[$i]));
        }

        return $result === 0;
    }
}