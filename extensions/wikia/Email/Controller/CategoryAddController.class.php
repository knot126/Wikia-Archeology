<?php

namespace Email\Controller;

use \Email\EmailController;
use Email\Check;

class CategoryAddController extends EmailController {

	/** @var \Title */
	private $categoryPage;

	/** @var \Title */
	private $pageAddedToCategory;

	public function initEmail() {
		$pageTitle = $this->request->getVal( 'pageTitle' );
		$titleNamespace = $this->request->getVal( 'namespace', NS_MAIN );
		$categoryPageId = $this->getVal( 'childArticleID' );

		$this->pageAddedToCategory = \Title::newFromText( $pageTitle, $titleNamespace );
		$this->categoryPage = \Title::newFromID( $categoryPageId );

		$this->assertValidParams();
	}

	/**
	 * Validate the params passed in by the client
	 */
	protected function assertValidParams() {
		$this->assertValidCategoryPage();
		$this->assertValidPageAddedToCategory();
	}

	/**
	 * @throws \Email\Check
	 */
	protected function assertValidCategoryPage() {
		if ( !$this->categoryPage instanceof \Title ) {
			throw new Check( "Invalid value passed for categoryPageId (param: childArticleId)" );
		}

		if ( !$this->categoryPage->exists() ) {
			throw new Check( "Category Page doesn't exist." );
		}
	}

	/**
	 * @throws \Email\Check
	 */
	protected function assertValidPageAddedToCategory() {
		if ( !$this->pageAddedToCategory instanceof \Title ) {
			throw new Check( "Invalid value passed for pageAddedToCategory (param: pageTitle)" );
		}

		if ( !$this->pageAddedToCategory->exists() ) {
			throw new Check( "pageAddedToCategory doesn't exist." );
		}
	}

	/**
	 * @template categoryAdd
	 */
	public function body() {
		$this->response->setData( [
			'salutation' => $this->getSalutation(),
			'summary' => $this->getDetails(),
			'categoryPageName' => $this->categoryPage->getText(),
			'pageAddedToCategoryName' => $this->pageAddedToCategory->getText(),
			'pageAddedToCategoryUrl' => $this->pageAddedToCategory->getFullURL(),
			'contentFooterMessages' => [
				$this->getContentFooterMessages()
			]
		] );
	}

	protected function getSubject() {
		return $this->getMessage( 'emailext-categoryadd-subject', $this->pageAddedToCategory->getText() )->parse();
	}

	protected function getDetails() {
		return $this->getMessage( 'emailext-categoryadd-details' )->text();
	}

	protected function getContentFooterMessages() {
		return $this->getMessage( 'emailext-categoryadd-footer-1',
			$this->categoryPage->getFullURL(),
			$this->categoryPage->getText()
		)->parse();
	}

	protected function getFooterMessages() {
		$footerMessages = [
			$this->getMessage( 'emailext-unfollow-text',
				$this->categoryPage->getCanonicalUrl( 'action=unwatch' ),
				$this->categoryPage->getPrefixedText() )->parse()
		];
		return array_merge( $footerMessages, parent::getFooterMessages() );
	}
}
