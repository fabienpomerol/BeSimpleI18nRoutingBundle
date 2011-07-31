<?php

namespace BeSimple\I18nRoutingBundle\Tests\Routing;

use BeSimple\I18nRoutingBundle\Routing\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testMatchLocaleRoute()
    {
        $parentRouter = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $parentRouter
            ->expects($this->at(0))
            ->method('match')
            ->with($this->equalTo('/foo'))
            ->will($this->returnValue(array('_route' => 'test.en', '_locale' => 'en')))
        ;
        $parentRouter
            ->expects($this->at(1))
            ->method('match')
            ->with($this->equalTo('/bar'))
            ->will($this->returnValue(array('_route' => 'test.de', '_locale' => 'de')))
        ;

        $router = new Router($parentRouter);

        $data = $router->match('/foo');
        $this->assertEquals('en', $data['_locale']);
        $this->assertEquals('test', $data['_route']);

        $data = $router->match('/bar');
        $this->assertEquals('de', $data['_locale']);
        $this->assertEquals('test', $data['_route']);
    }

    public function testMatchTranslateStringField()
    {
        $parentRouter = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $parentRouter->expects($this->any())
            ->method('match')
            ->with($this->equalTo('/foo/beberlei'))
            ->will($this->returnValue(array('_route' => 'test.en', '_locale' => 'en', '_translate' => 'name', 'name' => 'beberlei')))
        ;
        $translator = $this->getMock('BeSimple\I18nRoutingBundle\Routing\Translator\AttributeTranslatorInterface');
        $translator
            ->expects($this->once())
            ->method('translate')
            ->with($this->equalTo('test'), $this->equalTo('en'), $this->equalTo('name'), $this->equalTo('beberlei'))
            ->will($this->returnValue('Benjamin'))
        ;
        $router = new Router($parentRouter, null, $translator);

        $data = $router->match('/foo/beberlei');
        $this->assertEquals('en', $data['_locale']);
        $this->assertEquals('test', $data['_route']);
        $this->assertEquals('Benjamin', $data['name']);
    }

    public function testGenerateI18n()
    {
        $parentRouter = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $parentRouter->expects($this->once())
            ->method('generate')
            ->with($this->equalTo('test_route.en'), $this->equalTo(array('foo' => 'bar')), $this->equalTo(false))
        ;
        $router = new Router($parentRouter);

        $router->generate('test_route', array('foo' => 'bar', 'locale' => 'en'), false);
    }

    public function testGenerateDefault()
    {
        $parentRouter = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $parentRouter->expects($this->once())
            ->method('generate')
            ->with($this->equalTo('test_route'), $this->equalTo(array('foo' => 'bar')), $this->equalTo(false))
        ;
        $router = new Router($parentRouter);

        $router->generate('test_route', array('foo' => 'bar'), false);
    }

    public function testGenerateI18nTranslated()
    {
        $parentRouter = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $parentRouter->expects($this->once())
            ->method('generate')
            ->with($this->equalTo('test_route.en'), $this->equalTo(array('foo' => 'baz')), $this->equalTo(false))
        ;
        $translator = $this->getMock('BeSimple\I18nRoutingBundle\Routing\Translator\AttributeTranslatorInterface');
        $translator
            ->expects($this->once())
            ->method('reverseTranslate')
            ->with($this->equalTo('test_route'), $this->equalTo('en'), $this->equalTo('foo'), $this->equalTo('bar'))
            ->will($this->returnValue('baz'))
        ;
        $router = new Router($parentRouter, null, $translator);

        $router->generate('test_route', array('foo' => 'bar', 'translate' => 'foo', 'locale' => 'en'), false);
    }

    public function testGenerateI18nTranslatedDefaultSessionLocale()
    {
        $parentRouter = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $parentRouter->expects($this->once())
            ->method('generate')
            ->with($this->equalTo('test_route.fr'), $this->equalTo(array('foo' => 'baz')), $this->equalTo(false))
        ;
        $translator = $this->getMock('BeSimple\I18nRoutingBundle\Routing\Translator\AttributeTranslatorInterface');
        $translator
            ->expects($this->once())
            ->method('reverseTranslate')
            ->with($this->equalTo('test_route'), $this->equalTo('fr'), $this->equalTo('foo'), $this->equalTo('bar'))
            ->will($this->returnValue('baz'))
        ;
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session')
            ->disableOriginalConstructor()
            ->setMethods(array('getLocale'))
            ->getMock();
        $session
            ->expects($this->any())
            ->method('getLocale')
            ->will($this->returnValue('fr'))
        ;
        $router = new Router($parentRouter, $session, $translator);

        $router->generate('test_route', array('foo' => 'bar', 'translate' => 'foo'), false);
    }
}
