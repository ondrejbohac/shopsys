<?php

namespace SS6\ShopBundle\Model\Mail;

use SS6\ShopBundle\Component\Validator;
use SS6\ShopBundle\Model\Mail\MailTemplate;

/**
 * @Validator\Auto(entity="SS6\ShopBundle\Model\Article\Article")
 */
class MailTemplateData {

	/**
	 * @var string|null
	 */
	public $name;

	/**
	 * @var string|null
	 */
	public $bccEmail;

	/**
	 * @var string|null
	 */
	public $subject;

	/**
	 * @var string|null
	 */
	public $body;

	/**
	 * @var bool
	 */
	public $sendMail;

	/**
	 * @param string|null $name
	 * @param string|null $subject
	 * @param string|null $body
	 * @param bool $sendMail
	 * @param string|null $bccEmail
	 */
	public function __construct($name = null, $subject = null, $body = null, $sendMail = false, $bccEmail = null) {
		$this->name = $name;
		$this->subject = $subject;
		$this->body = $body;
		$this->sendMail = $sendMail;
		$this->bccEmail = $bccEmail;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Mail\MailTemplate $mailTemplate
	 */
	public function setFromEntity(MailTemplate $mailTemplate) {
		$this->name = $mailTemplate->getName();
		$this->bccEmail = $mailTemplate->getBccEmail();
		$this->subject = $mailTemplate->getSubject();
		$this->body = $mailTemplate->getBody();
		$this->sendMail = $mailTemplate->isSendMail();
	}

}
