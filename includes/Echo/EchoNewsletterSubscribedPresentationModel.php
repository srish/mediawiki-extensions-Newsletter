<?php

class EchoNewsletterSubscribedPresentationModel extends BaseNewsletterPresentationModel {

	public function getIconType() {
		return 'site';
	}

	public function getPrimaryLink() {
		return array(
			'url' => $this->getNewsletterUrl(),
			'label' => $this->msg( 'newsletter-notification-subscribed' )
				->params( $this->getNewsletterName() )
		);
	}

	public function getHeaderMessage() {
		list( $agentFormattedName, $agentGenderName ) = $this->getAgentForOutput();
		$msg = $this->msg( 'newsletter-notification-subscribed' );
		$msg->params( $this->getNewsletterName() );
		return $msg;
	}
}
