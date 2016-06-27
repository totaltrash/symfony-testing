<?php

namespace Totaltrash\Testing\Web;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Totaltrash\Testing\Symfony\ContainerAware;

abstract class WebTestCase extends BaseWebTestCase
{
    use ContainerAware;

    /**
     * @var Behat\Mink\Session
     */
    private static $session;

    /**
     * @return Behat\Mink\Session
     */
    public function getSession()
    {
        if (null == self::$session) {
            $driver = new \Behat\Mink\Driver\BrowserKitDriver(static::createClient());

            self::$session = new \Behat\Mink\Session($driver);

            // start the session
            self::$session->start();
        }

        return self::$session;
    }

    /**
     * Visit a route.
     * 
     * @param string $route
     * @param array  $parameters
     */
    protected function visitRoute($route, array $parameters = array())
    {
        $url = $this->getUrl($route, $parameters);
        $this
            ->getSession()
            ->visit($url)
        ;
    }

    /**
     * Send a post request.
     * 
     * @param string $route
     * @param array  $routeParameters   Parameters to build the route
     * @param array  $requestParameters Parameters to include in the body of the request (assoc array)
     */
    protected function sendPostRequest($route, $routeParameters = array(), $requestParameters = array())
    {
        $url = $this->getUrl($route, $routeParameters);
        $this
            ->getClient()
            ->request('POST', $url, $requestParameters, array())
        ;
    }

    /**
     * Fill in a hidden field.
     * 
     * @param string $fieldId
     * @param string $value
     */
    protected function fillHiddenField($fieldId, $value)
    {
        $this
            ->getPage()
            ->find('css', 'input[id="'.$fieldId.'"]')
            ->setValue($value)
        ;
    }

    /**
     * Submit a form.
     *
     * Call with something like `$this->submitForm('@name="name-of-the-form"');`
     * or change the xpath criteria to suit
     *
     * Still needed?
     * 
     * @param string $formPath
     */
    protected function submitForm($formPath)
    {
        $this
            ->getPage()
            ->find('xpath', 'descendant-or-self::form['.$formPath.']')
            ->submit()
        ;
    }

    /**
     * Follow client redirection once.
     */
    protected function followRedirect()
    {
        $this
            ->getClient()
            ->followRedirect()
        ;
    }

    /**
     * Disable the automatic following of redirections.
     */
    protected function disableFollowRedirects()
    {
        $this
            ->getClient()
            ->followRedirects(false)
        ;
    }

    /**
     * Restore the automatic following of redirections.
     */
    protected function restoreFollowRedirects()
    {
        $this
            ->getClient()
            ->followRedirects(true)
        ;
    }

    /**
     * Get the URL for a given route and parameters.
     * 
     * @param string $route
     * @param array  $parameters
     *
     * @return string
     */
    protected function getUrl($route, array $parameters = array())
    {
        $url = $this->getContainer()->get('router')->generate(
            str_replace(' ', '_', $route), $parameters, false
        );

        return $url;
    }

    /**
     * Get the Mink client.
     */
    protected function getClient()
    {
        return $this
            ->getSession()
            ->getDriver()
            ->getClient()
        ;
    }

    /**
     * Get the current Mink page.
     */
    protected function getPage()
    {
        return $this
            ->getSession()
            ->getPage()
        ;
    }
}
