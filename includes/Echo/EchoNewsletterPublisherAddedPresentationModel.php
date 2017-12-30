<?php

class EchoNewsletterPublisherAddedPresentationModel extends BaseNewsletterPresentationModel {

	public function getIconType() {
		return 'site';
	}

	public function getPrimaryLink() {
		return [
			'url' => $this->getNewsletterUrl(),
			'label' => $this->msg( 'newsletter-notification-link-text-new-publisher' )->text()
		];
	}

	public function getHeaderMessage() {
		list( $agentFormattedName, $agentGenderName ) = $this->getAgentForOutput();
		$msg = $this->msg( 'notification-header-newsletter-newpublisher' );
		$msg->params( $this->getNewsletterName() );
		$msg->params( $this->getViewingUserForGender() );
		$msg->params( $agentGenderName );
		return $msg;
	}

}
