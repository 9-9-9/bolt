<?php
namespace Bolt\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test correct operation of src/Controller/Backend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class LoginTest extends BoltUnitTest
{
    public function testPostLogin()
    {
        $app = $this->getApp();

        $request = Request::create('/bolt/login','POST', array('action'=>'login','username'=>'test','password'=>'pass'));

        $users = $this->getMock('Bolt\Users', array('login'), array($app));
        $users->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(true));

        $app['users'] = $users;
        $app->run($request);
        $this->expectOutputRegex("/Redirecting to \/bolt\//");

    }

    public function testPostLoginFailures()
    {
        $app = $this->getApp();

        $request = Request::create('/bolt/login','POST', array('action'=>'login','username'=>'test','password'=>'pass'));

        $users = $this->getMock('Bolt\Users', array('login'), array($app));
        $users->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(false));

        $app['users'] = $users;
        $this->checkTwigForTemplate($app, 'login/login.twig');
        $app->run($request);


        // Test missing data fails
        $app = $this->getApp();
        $request = Request::create('/bolt/login','POST', array('action'=>'fake'));
        $this->checkTwigForTemplate($app, 'error.twig');
        $app->run($request);

        $app = $this->getApp();
        $request = Request::create('/bolt/login','POST', array());
        $this->checkTwigForTemplate($app, 'error.twig');
        $app->run($request);

    }

    public function testLoginSuccess()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('login'), array($app));
        $users->expects($this->any())
            ->method('login')
            ->will($this->returnValue(true));
        $app['users'] = $users;
        $request = Request::create('/bolt/login','POST', array('action'=>'login'));
        $this->expectOutputRegex("/Redirecting to \/bolt\//");
        $app->run($request);
    }

    public function testResetRequest()
    {
        $app = $this->getApp();
        $app['swiftmailer.transport'] = new \Swift_Transport_NullTransport($app['swiftmailer.transport.eventdispatcher']);
        $users = $this->getMock('Bolt\Users', array('login', 'resetPasswordRequest'), array($app));
        $users->expects($this->any())
            ->method('login')
            ->will($this->returnValue(true));

        $users->expects($this->once())
            ->method('resetPasswordRequest')
            ->with($this->equalTo('admin'))
            ->will($this->returnValue(true));

        $app['users'] = $users;

        // Test missing username fails
        $request = Request::create('/bolt/login','POST', array('action'=>'reset'));
        $response = $app->handle($request);
        $this->assertRegExp('/Please provide a username/i',$response->getContent());

        // Test normal operation
        $request = Request::create('/bolt/login','POST', array('action'=>'reset', 'username'=>'admin'));
        $this->expectOutputRegex("/Redirecting to \/bolt\/login/");
        $app->run($request);
    }

    public function testLogout()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('logout'), array($app));
        $users->expects($this->once())
            ->method('logout')
            ->will($this->returnValue(true));

        $app['users'] = $users;

        $request = Request::create('/bolt/logout','POST', array());
        $this->expectOutputRegex("/Redirecting to \/bolt\/login/");
        $app->run($request);

    }

    public function testResetPassword()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('resetPasswordConfirm'), array($app));
        $users->expects($this->once())
            ->method('resetPasswordConfirm')
            ->will($this->returnValue(true));

        $app['users'] = $users;
        $request = Request::create('/bolt/resetpassword');
        $this->expectOutputRegex("/Redirecting to \/bolt\/login/");

        $app->run($request);
    }
}
