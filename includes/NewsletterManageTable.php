<?php

class NewsletterManageTable extends TablePager {

	public static $newsletterOwners = array();

	public function getFieldNames() {
		$header = null;
		if ( is_null( $header ) ) {
			$header = array();
			foreach ( SpecialNewsletterManage::$fields as $key => $value ) {
				$header[$key] = $this->msg( "newsletter-manage-header-$value" )->text();
			}
		}

		return $header;

	}

	public function getQueryInfo() {
		$info = array(
			'tables' => array( 'nl_publishers' ),
			'fields' => array(
				'newsletter_id',
				'publisher_id',
			),
		);

		// get user ids of all newsletter owners
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'nl_newsletters',
			array( 'nl_owner_id', 'nl_id' ),
			array(),
			__METHOD__,
			array( 'DISTINCT' )
		);
		foreach ( $res as $row ) {
			self::$newsletterOwners[$row->nl_id] = $row->nl_owner_id;
		}

		return $info;
	}

	public function formatValue( $field, $value ) {
		static $previous;

		switch ( $field ) {
			case 'newsletter_id':
				if ( $previous === $value ) {

					return null;
				} else {
					$dbr = wfGetDB( DB_SLAVE );
					$res = $dbr->select(
						'nl_newsletters',
						array( 'nl_name' ),
						array( 'nl_id' => $value ),
						__METHOD__,
						array()
					);
					$newsletterName = null;
					foreach ( $res as $row ) {
						$newsletterName = $row->nl_name;
					}
					$previous = $value;

					return $newsletterName;
				}
			case 'publisher_id' :
				$user = User::newFromId( $value );

				return $user->getName();
			case 'permissions' :
				$radioOwner = HTML::element(
						'input',
						array(
							'type' => 'checkbox',
							'disabled' => 'true',
							'id' => 'newslettermanage',
							'checked' => self::$newsletterOwners[$this->mCurrentRow->newsletter_id]
							=== $this->mCurrentRow->publisher_id ? true : false,
						)
					) . $this->msg( 'newsletter-owner-radiobutton-label' );

				$radioPublisher = HTML::element(
						'input',
						array(
							'type' => 'checkbox',
							'disabled' => 'true',
							'id' => 'newslettermanage',
							'checked' => self::$newsletterOwners[$this->mCurrentRow->newsletter_id]
							=== $this->mCurrentRow->publisher_id ? false : true,
						)
					) . $this->msg( 'newsletter-publisher-radiobutton-label' );

				return $radioOwner . $radioPublisher;
			case 'action' :
				$remButton = HTML::element(
					'input',
					array(
						'type' => 'button',
						'value' => 'Remove',
						'name' => $previous,
						'id' => $this->mCurrentRow->publisher_id,
					)
				);

				return ( self::$newsletterOwners[$this->mCurrentRow->newsletter_id] !==
					$this->mCurrentRow->publisher_id &&
					self::$newsletterOwners[$this->mCurrentRow->newsletter_id] ==
					$this->getUser()->getId() ) ? $remButton : '';

		}
	}

	public function getCellAttrs( $field, $value ) {
		return array(
			'align' => 'center',
		);
	}

	public function getDefaultSort() {
		return 'newsletter_id';
	}

	public function isFieldSortable( $field ) {
		return false;
	}

}
