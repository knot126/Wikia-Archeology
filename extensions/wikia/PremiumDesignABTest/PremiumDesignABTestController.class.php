<?php

class PremiumDesignABTestController extends WikiaController {

	public function header() {

	}

	public function A_header() {
		$this->headerModuleParams = [ 'showSearchBox' => false ];
	}

	public function B_header() {
		$this->headerModuleParams = [ 'showSearchBox' => false ];
	}

	public function pageheader() {

	}

	public function A_pageheader() {
		global $wgExtensionsPath;
		$this->videoPlayButtonSrc = $wgExtensionsPath . '/wikia/PremiumDesignABTest/images/play-button.png';
	}

	public function B_pageheader() {

	}

	public function C_pageheader() {
		$this->headerModuleParams = [ 'showSearchBox' => false ];
	}

	public function D_pageheader() {
		$this->headerModuleParams = [ 'showSearchBox' => false ];
	}

	public function rightrail() {

	}

	public function C_rightrail() {
		// number of pages on this wiki
		$this->tallyMsg = wfMessage( 'oasis-total-articles-mainpage', SiteStats::articles() )->parse();
	}

	public function D_rightrail() {
		// number of pages on this wiki
		$this->tallyMsg = wfMessage( 'oasis-total-articles-mainpage', SiteStats::articles() )->parse();
	}

	public function video() {
		global $wgExtensionsPath;
		$this->videoPlayButtonSrc = $wgExtensionsPath . '/wikia/PremiumDesignABTest/images/play-button.png';
	}
}
