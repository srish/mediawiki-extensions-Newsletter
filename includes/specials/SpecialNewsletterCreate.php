<?php

/**
 * Special page for creating newsletters
 *
 * @license GNU GPL v2+
 * @author Tina Johnson
 */
class SpecialNewsletterCreate extends FormSpecialPage {

	/**
	 * @var Newsletter
	 */
	protected $newsletter;

	public function __construct() {
		parent::__construct( 'NewsletterCreate', 'newsletter-create' );
	}

	public function execute( $par ) {
		$this->requireLogin();
		parent::execute( $par );
		$this->getOutput()->setSubtitle(
			Linker::linkKnown(
				SpecialPage::getTitleFor( 'Newsletters' ),
				$this->msg( 'newsletter-subtitlelinks-list' )->escaped()
			)
		);
	}

	/**
	 * @param HTMLForm $form
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->setSubmitTextMsg( 'newsletter-create-submit' );
	}

	/**
	 * @return array
	 */
	protected function getFormFields() {
		return array(
			'name' => array(
				'name' => 'newsletter',
				'type' => 'text',
				'required' => true,
				'label-message' => 'newsletter-name',
				'maxlength' => 120,
			),
			'mainpage' => array(
				'type' => 'title',
				'required' => true,
				'label-message' => 'newsletter-title',
			),
			'description' => array(
				'type' => 'textarea',
				'required' => true,
				'label-message' => 'newsletter-desc',
				'rows' => 15,
				'maxlength' => 600000,
			),
		);
	}

	/**
	 * Do input validation, error handling and create a new newletter.
	 *
	 * @param array $input The data entered by user in the form
	 * @throws ThrottledError
	 * @return Status
	 */
	public function onSubmit( array $input ) {
		global $wgContLang;

		$data = array(
			'Name' => trim( $input['name'] ),
			'Description' => trim( $input['description'] ),
			'MainPage' => Title::newFromText( $input['mainpage'] ),
		);

		$validator = new NewsletterValidator( $data );
		$validation = $validator->validate();
		if ( !$validation->isGood() ) {
			// Invalid input was entered
			return $validation;
		}

		$mainPageId = $data['MainPage']->getArticleID();

		$dbr = wfGetDB( DB_SLAVE );
		$rows = $dbr->select(
			'nl_newsletters',
			array( 'nl_name', 'nl_main_page_id' ),
			$dbr->makeList(
				array(
					'nl_name' => $data['Name'],
					'nl_main_page_id' => $mainPageId,
				 ),
				 LIST_OR
			)
		);
		// Check whether another existing newsletter has the same name or main page
		foreach ( $rows as $row ) {
			if ( $row->nl_name === $data['Name'] ) {
				return Status::newFatal( 'newsletter-exist-error', $data['Name'] );
			} elseif ( (int)$row->nl_main_page_id === $mainPageId ) {
				return Status::newFatal( 'newsletter-mainpage-in-use' );
			}
		}

		$user = $this->getUser();
		if ( $user->pingLimiter( 'newsletter' ) ) {
			// Default user access level for creating a newsletter is quite low
			// so add a throttle here to prevent abuse (eg. mass vandalism spree)
			throw new ThrottledError;
		}

		$store = NewsletterStore::getDefaultInstance();
		$this->newsletter = new Newsletter( 0,
			$data['Name'],
			// nl_newsletters.nl_desc is a blob but put some limit
			// here which is less than the max size for blobs
			$wgContLang->truncate( $data['Description'], 600000 ),
			$mainPageId
		);
		$newsletterCreated = $store->addNewsletter( $this->newsletter );

		if ( $newsletterCreated ) {
			$title = Title::makeTitleSafe( NS_NEWSLETTER, trim( $data['Name'] ) );
			$editSummaryMsg = $this->msg( 'newsletter-create-editsummary' );
			$result = NewsletterContentHandler::edit(
				$title,
				$data['Description'],
				$input['mainpage'],
				array( $user->getName() ),
				$editSummaryMsg->inContentLanguage()->plain(),
				$this->getContext()
			);
			if ( $result->isGood() ) {
				$this->onPostCreation( $user );
				return Status::newGood();
			} else {
				// The content creation was unsuccessful, lets rollback the newsletter from db
				$store->rollBackNewsletterAddition( $this->newsletter );
			}
		}

		// Couldn't insert to the DB..
		return Status::newFatal( 'newsletter-create-error' );
	}

	/**
	 * Subscribe and add the creator to the publisher's list of the
	 * newly created newsletter.
	 *
	 * @param User $user User object of the creator
	 */
	private function onPostCreation( User $user ) {
		$this->newsletter->subscribe( $user );
		NewsletterStore::getDefaultInstance()->addPublisher( $this->newsletter, $user );
	}

	public function onSuccess() {
		$this->getOutput()->addWikiMsg( 'newsletter-create-confirmation', $this->newsletter->getName() );
	}

	public function doesWrites() {
		return true;
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}
}
