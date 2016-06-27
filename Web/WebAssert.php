<?php

namespace Totaltrash\Testing\Web;

use Behat\Mink\Session;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\WebAssert as BaseWebAssert;
use Totaltrash\Testing\Assert\Assert;
use BadMethodCallException;
use RuntimeException;

/**
 * Provide a bridge between Minks WebAssert and PHPUnits Assert strategies.
 *
 * Also, provide other generic assertions for use in a web context.
 */
class WebAssert extends Assert
{
    /**
     * @var Behat\Mink\Session
     */
    protected $session;

    /**
     * @var Behat\Mink\WebAssert
     */
    protected $minkWebAssert;

    /**
     * Constructor.
     * 
     * @param Mink $mink
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->minkWebAssert = new BaseWebAssert($session);
    }

    /**
     * Hand off all other calls to the mink web assert. Passes Mink ExpectationExceptions
     * to PHPUnits assert library so assertions are counted, and result in failures instead
     * of errors.
     *
     * @see https://gist.github.com/shashikantjagtap/3c25990235e7c343de42 
     * @see http://www.bbc.co.uk/blogs/internet/entries/79fd4cb1-621e-4a7c-923d-08df8956c675
     */
    public function __call($name, $args)
    {
        if (!is_callable([$this->minkWebAssert, $name])) {
            throw new BadMethodCallException(sprintf(
                'Tried to call "%s" on WebAssert but that method does not exist',
                $name
            ));
        }

        $success = true;
        $failMessage = '';

        try {
            $return = call_user_func_array(array($this->minkWebAssert, $name), $args);
        } catch (ExpectationException $e) {
            $success = false;
            $failMessage = $e->getMessage();
        }

        $this->assert($success, $failMessage);

        return $return;
    }

    /**
     * Assert the current page corresponds to the given route and optional
     * parameters.
     * 
     * @param string $route
     * @param array  $parameters
     */
    public function currentRoute($route, array $parameters = array())
    {
        $url = $this->getUrl($route, $parameters);
        $this->addressEquals($url);
        $this->statusCodeEquals(200);
    }

    /**
     * Assert an email was sent to a given address.
     *
     * Requires profile to be collected in config_test.yml.
     * 
     * Also, don't forget to disable redirects if the email is sent during
     * an action that redirects.
     * 
     * @param string $email Address the email expected to be sent to
     */
    public function emailSentTo($email)
    {
        $messages = $this
            ->getSymfonyProfile()
            ->getCollector('swiftmailer')
            ->getMessages()
        ;

        $sent = false;
        foreach ($messages as $message) {
            foreach ($message->getTo() as $key => $to) {
                if ($key === $email) {
                    $sent = true;
                    break 2;
                }
            }
        }

        $this->assert($sent, sprintf(
            'No email sent to %s',
            $email
        ));
    }

    /**
     * Assert the maximum number of queries has not been exceeded.
     * 
     * @param int $maxCount
     */
    public function maxQueryCount($maxCount)
    {
        $actualCount = $this
            ->getSymfonyProfile()
            ->getCollector('db')
            ->getQueryCount()
        ;

        $this->assert($actualCount <= $maxCount, sprintf(
            'Too many database queries - expected no more than %d, got %d',
            $maxCount,
            $actualCount
        ));
    }

    /**
     * Assert the elements contain the expected values.
     *
     * Not tested
     * 
     * @param string $selector
     * @param array  $expectedValues
     */
    public function elementsContainingInOrder($selector, array $expectedValues)
    {
        //get the elements
        $actualElements = $this->session->getPage()->findAll('css', $selector);

        //first check the elements count matches the table rows
        if (count($actualElements) !== count($expectedValues)) {
            throw new \Exception(sprintf(
                'Expecting %d elements matching "%s", got %d',
                count($expectedValues),
                $selector,
                count($actualElements)
            ));
        }

        //now iterate all the elements, and ensure they contain what is expected
        for ($index = 0; $index < count($actualElements); ++$index) {
            $row = $table->getRow($index);
            $text = $row[0];

            $element = $actualElements[$index];

            $regex = '/'.preg_quote($text, '/').'/ui';
            $actual = $element->getText();

            $this->assert(preg_match($regex, $actual), sprintf(
                'The text "%s" was not found in the text of the element matching "%s" at index %d, got "%s".',
                $text,
                $selector,
                $index,
                $element->getText()
            ));
        }
    }

    /**
     * Assert the table row contains the expected values.
     *
     * Borrowed and adapted from Behatch\TableContext
     * 
     * @see     https://github.com/Behatch/contexts/blob/master/src/Context/TableContext.php
     *
     * @param string $table    Selector for the table (#myTableId)
     * @param string $rowIndex Row number (1 based)
     * @param array  $expected An assoc array where the key identifies the column:
     *                         array( 
     *                         'col1' => 'Some Value', 
     *                         'col9' => 'Something in the 9th <td>',
     *                         )
     */
    public function tableRowMatches($table, $rowIndex, array $expected)
    {
        $rowsSelector = "$table tbody tr";
        $rows = $this->session->getPage()->findAll('css', $rowsSelector);

        if (!isset($rows[$rowIndex - 1])) {
            throw new \Exception("The row $rowIndex was not found in the '$table' table");
        }

        $cells = (array) $rows[$rowIndex - 1]->findAll('css', 'td');
        $cells = array_merge((array) $rows[$rowIndex - 1]->findAll('css', 'th'), $cells);

        foreach (array_keys($expected) as $columnName) {
            // Extract index from column. ex "col2" -> 2
            preg_match('/^col(?P<index>\d+)$/', $columnName, $matches);
            $cellIndex = (int) $matches['index'] - 1;

            $this->assert($expected[$columnName] == $cells[$cellIndex]->getText(), sprintf(
                'Table "%s", row "%d", cell "%s" - expected "%s", got "%s"',
                $table,
                $rowIndex,
                $columnName,
                $expected[$columnName],
                $cells[$cellIndex]->getText()
            ));
        }
    }

    /**
     * Assert the page contains a link, and optionally assert the href of the link 
     * matches the route.
     * 
     * @param string $linkText
     * @param string $route
     * @param array  $routeParameters
     */
    public function hasLink($linkText, $route = null, $routeParameters = array())
    {
        $node = $this->elementExists('xpath', '//a[contains(text(), \''.$linkText.'\')]');

        if ($route !== null) {
            $expectedUrl = $this->getUrl($route, $routeParameters);
            $actualUrl = $node->getAttribute('href');

            $this->assert($actualUrl === $expectedUrl, sprintf(
                "Expected link containing '%s' to have href '%s', got '%s'",
                $linkText,
                $expectedUrl,
                $actualUrl
            ));
        }
    }

    /**
     * Assert the page does not have a link containing the given text.
     * 
     * @param string $linkText
     */
    public function hasNotLink($linkText)
    {
        $this->elementNotExists('xpath', '//a[contains(text(), \''.$linkText.'\')]');
    }

    /**
     * Assert the select has an expected option.
     * 
     * @param string $select
     * @param string $option
     */
    public function selectContainsOption($select, $option)
    {
        $this->assert($this->checkSelectContainsOption($select, $option) === true, sprintf(
            'Select "%s" does not contain option "%s"',
            $select,
            $option
        ));
    }

    /**
     * Assert the select does not have an option.
     * 
     * @param string $select
     * @param string $option
     */
    public function selectNotContainsOption($select, $option)
    {
        $this->assert($this->checkSelectContainsOption($select, $option) === false, sprintf(
            'Select "%s" does contain option "%s"',
            $select,
            $option
        ));
    }

    /**
     * Check if a select has an option.
     * 
     * @param string $select
     * @param string $option
     *
     * @return bool True if the option found in the select, else false
     */
    protected function checkSelectContainsOption($select, $option)
    {
        $obj = $this->session->getPage()->findField($select);
        if ($obj === null) {
            throw new \Exception(sprintf(
                'Select box "%s" not found',
                $select
            ));
        }
        $optionText = $obj->getText();

        $regex = '/'.preg_quote($option, '/').'/ui';

        return preg_match($regex, $optionText) > 0;
    }

    /**
     * Get the container.
     */
    protected function getContainer()
    {
        return $this->getSymfonyDriver()->getClient()->getContainer();
    }

    /**
     * Get the symfony profiler.
     * 
     * @return Symfony\Component\HttpKernel\Profiler\Profile
     *
     * @throws RuntimeException when profiler is disabled
     */
    protected function getSymfonyProfile()
    {
        $profile = $this->getSymfonyDriver()->getClient()->getProfile();

        if ($profile === false) {
            throw new RuntimeException(
                'Profiler is disabled. Set framework:profiler:collect to true in config_test.yml'
            );
        }

        return $profile;
    }

    /**
     * Get the Symfony driver.
     * 
     * @return BrowserKitDriver
     *
     * @throws UnsupportedDriverActionException when not using mink browser kit driver
     */
    protected function getSymfonyDriver()
    {
        $driver = $this->session->getDriver();
        if ($driver instanceof BrowserKitDriver === false) {
            throw new UnsupportedDriverActionException(
                'Not using the Symfony Driver - current driver is %s',
                $driver
            );
        }

        return $driver;
    }

    /**
     * Resolve the URL for a given route and optional parameters.
     * 
     * @param string $route
     * @param array  $parameters
     *
     * @return string
     */
    protected function getUrl($route, array $parameters = array())
    {
        $url = $this->getContainer()->get('router')->generate(
            str_replace(' ', '_', $route),
            $parameters
        );

        return $url;
    }
}
