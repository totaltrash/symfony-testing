Symfony container service mocker using Prophecy as a mock engine
================================================================

Installation
------------

1. Override `AppKernel::getContainerBaseClass`:

    /**
     * Override getContainerBaseClass to return the symfony mocker class
     * when testing
     * 
     * @return string
     */
    protected function getContainerBaseClass()
    {
        if ('test' == $this->environment) {
            return 'Totaltrash\Testing\Mocker\MockContainer';
        }

        return parent::getContainerBaseClass();
    }

Usage
-----

Use the mocker in a test:

    use Totaltrash\Testing\Mocker\Mocker;

    //...

    $this->mocker = new Mocker($this->getKernel());

    // Mock a service:
    $service = $this->mocker->mockService('service.name', 'AppBundle\SomeClass');

    // Create a standalone mock:
    $someMock = $this->mocker->createMock('AppBundle\SomeOtherClass');

    // Set the prophecy expected behaviour:
    $someMock
        ->breakIt()
        ->shouldBeCalled()
    ;

    $service
        ->doSomething()
        ->willReturn($someMock)
    ;


Check the predictions at end of test:

    $this->mocker->checkPredictions();


Reset the mocker between tests (usually in tearDown):

    protected function tearDown()
    {
        $this->mocker->reset();
    }


Run it so it fails first (to make sure everything is connected properly)!
