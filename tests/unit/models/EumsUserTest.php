<?php

Yii::import('eums.models.*');

class EumsUserTest extends CDbTestCase
{
	public $fixtures=array(
		'eumsUsers'=>'EumsUser',
	);

  /**
   * Mockup a Captcha.
   *
   * @return CCaptchaAction
   */
  protected function mockupCaptcha() {
    $c = $this->getMock("CController", array("actions"), array("test"));
    $c->expects($this->any())
      ->method("actions")
      ->will($this->returnValue(array("captcha"=>"CCaptchaAction")));
    Yii::app()->controller = $c;
    return new CCaptchaAction(Yii::app()->controller, 'captcha');
  }

	public function testOnRegistration() {
    $user = new EumsUser("registration");
    $user->setAttributes(array(
      'username'=>'tester0',
      'first_name'=>'Tester 0',
      'last_name'=>'Tester 0',
      'password'=>'asd',
      'password_confirm'=>'asd',
      'email'=>'tester0@test.com',
    ));
    $user->captcha = $this->mockupCaptcha()->verifyCode;
    $this->assertTrue($user->save());
    $this->assertGreaterThan(0, strlen($user->activation));
    $this->assertFalse($user->active);
	}

  public function testOnRegistrationFail() {
    $user = new EumsUser("registration");
    $user->setAttributes(array(
      'username'=>'tester0',
      'first_name'=>'Tester 0',
      'last_name'=>'Tester 0',
      'password'=>'asd',
      'password_confirm'=>'asd123',
      'email'=>'tester0@test.com',
    ));
    $user->captcha = $this->mockupCaptcha()->verifyCode;
    $this->assertFalse($user->save());
  }

  public function testOnDuplicateRegistration() {
    $user = new EumsUser("registration");
    $user->setAttributes(array(
      'username'=>'tester0',
      'first_name'=>'Tester 0',
      'last_name'=>'Tester 0',
      'password'=>'asd',
      'password_confirm'=>'asd',
      'email'=>'tester1@test.com',
    ));
    $user->captcha = $this->mockupCaptcha()->verifyCode;
    $this->assertFalse($user->save());
  }

  public function testOnPasswordReset() {
    $user = $this->getFixtureRecord("eumsUsers", "tester1");
    $user->setScenario("resetpassword");
    $user->setAttributes(array(
      'password'=>'bbc',
      'password_confirm'=>'bbc',
    ));
    $this->assertTrue($user->save());
    $user = EumsUser::model()->findByPk($user->getPrimaryKey());
    /** Password Changed */
    $this->assertTrue($user->authenticate('bbc'));
    $user->setAttributes(array(
      'password'=>'kkk',
      'password_confirm'=>'kkk',
    ));
    $user->save();
    $user = EumsUser::model()->findByPk($user->getPrimaryKey());
    /** Password did not change */
    $this->assertFalse($user->authenticate('kkk'));
  }

  public function testOnUserLoginOnNotActive() {
    /** @var $user EumsUser */
    $user = $this->getFixtureRecord("eumsUsers", "tester1");
    $user->active = false;
    $this->assertTrue($user->save());
    $user = EumsUser::model()->findByPk($user->getPrimaryKey());
    $this->assertFalse($user->authenticate("asd"));
  }

  public function testOnUserLoginOnActive() {
    /** @var $user EumsUser */
    $user = $this->getFixtureRecord("eumsUsers", "tester1");
    $this->assertTrue($user->authenticate("asd"));
  }

  public function testChangeSameUsernameFail() {
    $user = $this->getFixtureRecord("eumsUsers", "tester1");
    $user->username = "tester2";
    $this->assertFalse($user->save());
  }

  public function testChangeSameEmailFail() {
    $user = $this->getFixtureRecord("eumsUsers", "tester1");
    $user->email = "tester2@test.com";
    $this->assertFalse($user->save());
  }
}