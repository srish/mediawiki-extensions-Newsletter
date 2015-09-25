<?php

/**
 * @license GNU GPL v2+
 * @author Tina Johnson
 * @todo Optimize queries here
 */
class NewsletterTablePager extends TablePager {

	/**
	 * @var null|string[]
	 */
	private $fieldNames = null;

	public function __construct( IContextSource $context = null, IDatabase $readDb = null ) {
		if ( $readDb !== null ) {
			$this->mDb = $readDb;
		}
		parent::__construct( $context );
	}

	public function getFieldNames() {
		if ( $this->fieldNames === null ) {
			$this->fieldNames = array(
				'nl_name' => $this->msg( 'newsletter-header-name' )->text(),
				'nl_desc' => $this->msg( 'newsletter-header-description' )->text(),
				'nl_frequency' => $this->msg ( 'newsletter-header-frequency' )->text(),
				'subscriber_count' => $this->msg( 'newsletter-header-subscriber_count' )->text(),
				'action' => $this->msg( 'newsletter-header-action' )->text(),
			);
		}
		return $this->fieldNames;
	}

	public function getQueryInfo() {
		$userId = $this->getUser()->getId();
		//TODO we could probably just retrieve all subscribers IDs as a string here.
		$info = array(
			'tables' => array( 'nl_newsletters' ),
			'fields' => array(
				'nl_name',
				'nl_main_page_id',
				'nl_desc',
				'nl_id',
				'nl_frequency',
				'subscribers' => ( '( SELECT COUNT(*) FROM nl_subscriptions WHERE nls_newsletter_id = nl_id )' ),
				'current_user_subscribed' => "$userId IN (SELECT nls_subscriber_id FROM nl_subscriptions WHERE nls_newsletter_id = nl_id )" ,
			),
			'options' => array( 'DISTINCT nl_id' ),
		);

		return $info;
	}

	public function formatValue( $field, $value ) {
		switch ( $field ) {
			case 'nl_name':
				$title = Title::newFromID( $this->mCurrentRow->nl_main_page_id );
				if ( $title ) {
					return Linker::link( $title, htmlspecialchars( $value ) );
				} else {
					return htmlspecialchars( $value );
				}
			case 'nl_desc':
				return $value;
			case 'nl_frequency':
				return $value;
			case 'subscriber_count':
				return HTML::element(
					'input',
					array(
						'type' => 'textbox',
						'readonly' => 'true',
						'id' => 'newsletter-' . $this->mCurrentRow->nl_id,
						'value' => $this->mCurrentRow->subscribers,

					)
				);
			case 'action' :
				$radioSubscribe = Html::element(
						'input',
						array(
							'type' => 'radio',
							'name' => 'nl_id-' . $this->mCurrentRow->nl_id,
							'value' => 'subscribe',
							'checked' => $this->mCurrentRow->current_user_subscribed,
						)
					) . $this->msg( 'newsletter-subscribe-button-label' );
				$radioUnSubscribe = Html::element(
						'input',
						array(
							'type' => 'radio',
							'name' => 'nl_id-' . $this->mCurrentRow->nl_id,
							'value' => 'unsubscribe',
							'checked' => !$this->mCurrentRow->current_user_subscribed,
						)
					) . $this->msg( 'newsletter-unsubscribe-button-label' );

				return $radioSubscribe . $radioUnSubscribe;
		}
	}

	public function getDefaultSort() {
		$this->mDefaultDirection = IndexPager::DIR_DESCENDING;
		return 'current_user_subscribed';
	}

	public function isFieldSortable( $field ) {
		return false;
	}

}
