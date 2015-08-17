<?php
/**
 * Special page for creating newsletters
 *
 */
class SpecialNewsletterCreate extends SpecialPage {
	public function __construct() {
		parent::__construct('NewsletterCreate');
	}

	public function execute( $par ) {
		$this->setHeaders();
		$output = $this->getOutput();
		$this->requireLogin();
		$createNewsletterArray = $this->getCreateFormFields();

		# Create HTML forms
		$createNewsletterForm = new HTMLForm( $createNewsletterArray, $this->getContext(), 'createnewsletterform' );
		$createNewsletterForm->setSubmitText( 'Create newsletter' );
		$createNewsletterForm->setSubmitCallback( array( 'SpecialNewsletterCreate', 'onSubmitNewsletter' ) );
		$createNewsletterForm->setWrapperLegendMsg( 'newsletter-create-section' );
		# Show HTML forms
		$createNewsletterForm->show();
		$output->returnToMain();
	}

	/**
	 * Function to generate Create Newsletter Form
	 *
	 * @return array
	 */
	protected function getCreateFormFields() {
		return array(
			'name' => array(
				'type' => 'text',
				'required' => true,
				'label' => $this->msg( 'newsletter-name' )
			),
			'description' => array(
				'type' => 'textarea',
				'required' => true,
				'label' => $this->msg( 'newsletter-desc' ),
				'rows' => 15,
				'cols' => 50,
			),
			'mainpage' => array(
				'required' => true,
				'type' => 'text',
				'label' => $this->msg( 'newsletter-title' )
			),
			'frequency' => array(
				'required' => true,
				'type' => 'selectorother',
				'label' => $this->msg( 'newsletter-frequency' ),
				'options' => array(
					'weekly' => $this->msg( 'newsletter-option-weekly' ),
					'monthly' => $this->msg( 'newsletter-option-monthly' ),
					'quarterly' => $this->msg( 'newsletter-option-quarterly' )
				),
				'size' => 18, # size of 'other' field
				'maxlength' => 50
			),
			'publisher' => array(
				'type' => 'hidden',
				'default' => $this->getUser()->getId()
			)
		);
	}

	/**
	 * Perform insert query on newsletter table with data retrieved from HTML
	 * form for creating newsletters
	 *
	 * @param array $formData The data entered by user in the form
	 * @return bool
	 */
	static function onSubmitNewsletter( array $formData ) {
		if ( isset( $formData['mainpage'] ) ) {
			$page = Title::newFromText( $formData['mainpage'] );
			$pageId = $page->getArticleId();
		} else {
			return 'Unknown Newsletter main page entered. Please try again';
		}
		if ( isset( $formData['name'] ) && isset( $formData['description'] ) && ( $pageId !== 0 ) &&
			isset( $formData['mainpage'] ) && isset( $formData['frequency'] ) && isset( $formData['publisher'] ) ) {
			//inserting into database
			$dbw = wfGetDB( DB_MASTER );
			$rowData = array(
				'nl_name' => $formData['name'],
				'nl_desc' => $formData['description'],
				'nl_main_page_id' => $pageId,
				'nl_frequency' => $formData['frequency'],
				'nl_owner_id' => $formData['publisher']
			);

			try {
				$dbw->insert( 'nl_newsletters', $rowData, __METHOD__ );
			} catch ( DBQueryError $e ) {
				return RequestContext::getMain()->msg( 'newsletter-exist-error' );
			}
			RequestContext::getMain()->getOutput()->addWikiMsg( 'newsletter-create-confirmation' );
			//Add newsletter creator as publisher
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'nl_newsletters',
				array( 'nl_id' ),
				array(
					'nl_name' => $formData['name']
				),
				__METHOD__
			);

			$newsletterId = array();
			foreach ( $res as $row ) {
				$newsletterId = $row->nl_id;
			}

			$pubRowData = array(
				'newsletter_id' => $newsletterId,
				'publisher_id' => $formData['publisher']
			);
			$dbw->insert( 'nl_publishers', $pubRowData, __METHOD__ );

			return true;
		}

		return RequestContext::getMain()->msg( 'newsletter-mainpage-not-found-error' );
	}
}