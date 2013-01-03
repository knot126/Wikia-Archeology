<?php
class MarketingToolboxModuleExploreService extends MarketingToolboxModuleService {
	const SECTION_FIELD_PREFIX = 'exploreSectionHeader';
	const LINK_TEXT = 'exploreLinkText';
	const LINK_URL = 'exploreLinkUrl';

	protected $lettersMap = array('a', 'b', 'c', 'd');

	/**
	 * @var MarketingToolboxExploreModel|null
	 */
	protected $model = null;

	public function __construct($langCode, $sectionId, $verticalId) {
		parent::__construct($langCode, $sectionId, $verticalId);

		$this->model = new MarketingToolboxExploreModel();
	}

	protected function getFormFields() {
		$formFields = array(
			'exploreTitle' => array(
				'label' => $this->wf->Msg('marketing-toolbox-hub-module-explore-title'),
				'validator' => new WikiaValidatorString(
					array(
						'required' => true,
						'min' => 1
					),
					array('too_short' => 'marketing-toolbox-validator-string-short')
				),
				'attributes' => array(
					'class' => 'required explore-title'
				)
			),
			'fileName' => array(
				'type' => 'hidden',
				'attributes' => array(
					'class' => 'wmu-file-name-input'
				),
				'validator' => new WikiaValidatorFileTitle(
					array(),
					array('wrong-file' => 'marketing-toolbox-validator-wrong-file')
				)
			),
		);
		$sectionsLimit = $this->model->getFormSectionsLimit();
		$linksLimit = $this->model->getLinksLimit();

		for($sectionIdx = 1; $sectionIdx <= $sectionsLimit; $sectionIdx++) {
			$formFields = $formFields + $this->generateSectionHeaderField($sectionIdx);

			for($linkIdx = 0; $linkIdx < $linksLimit; $linkIdx++) {
				$formFields = $formFields + $this->generateSectionLinkFields($sectionIdx, $linkIdx);
			}
		}

		return $formFields;
	}

	protected function generateSectionHeaderField($sectionIdx) {
		$fieldName = self::SECTION_FIELD_PREFIX . $sectionIdx;
		return array(
			$fieldName => array(
				'label' => $this->wf->MsgExt('marketing-toolbox-hub-module-explore-header', array('parseinline'), $sectionIdx),
				'validator' => new WikiaValidatorString(),
			),
		);
	}

	protected function generateSectionLinkFields($sectionIdx, $linkIdx) {
		$linkUrlFieldName = $this->generateUrlFieldName($sectionIdx, $linkIdx);

		$linkUrlField = array(
			'label' => $this->wf->Msg('marketing-toolbox-hub-module-explore-link-url'),
			'labelclass' => "wikiaUrlLabel",
			'validator' => new WikiaValidatorUrl(
				array(),
				array(
					'wrong' => 'marketing-toolbox-hub-module-explore-link-url-invalid'
				)
			),
			'attributes' => array(
				'class' => "wikiaUrl",
			),
			'icon' => true
		);

		$linkHeaderFieldName = self::LINK_TEXT . $sectionIdx . $this->lettersMap[$linkIdx];
		$linkHeaderField = array(
			'label' => $this->wf->MsgExt('marketing-toolbox-hub-module-explore-header', array('parseinline'), $this->lettersMap[$linkIdx]),
			'validator' => new WikiaValidatorDependent(
				array(
					'required' => false,
					'ownValidator' => new WikiaValidatorString(
						array(
							'required' => true,
							'min' => 1
						),
						array(
							'too_short' => 'marketing-toolbox-hub-module-explore-link-text-too-short-error'
						)
					),
					'dependentFields' => array(
						$linkUrlFieldName => new WikiaValidatorString(
							array(
								'required' => true,
								'min' => 1
							)
						)
					)
				)
			),
			'attributes' => array(
				'class' => "{required: '#MarketingToolbox{$linkUrlFieldName}:filled'}"
			)
		);

		return array(
			$linkHeaderFieldName => $linkHeaderField,
			$linkUrlFieldName => $linkUrlField,
		);
	}

	protected function generateUrlFieldName($sectionIdx, $linkIdx) {
		return  self::LINK_URL . $sectionIdx . $this->lettersMap[$linkIdx];
	}

	public function renderEditor($data) {
		$data['sectionLimit'] = $this->model->getFormSectionsLimit();
		
		if( !empty($data['values']['fileName']) ) {
			$model = new MarketingToolboxModel();
			$imageData = ImagesService::getLocalFileThumbUrlAndSizes($data['values']['fileName'], $model->getThumbnailSize());
			$data['fileUrl'] = $imageData->url;
			$data['imageWidth'] = $imageData->width;
			$data['imageHeight'] = $imageData->height;
		}
		
		return parent::renderEditor($data);
	}

	public function filterData($data) {
		$data = parent::filterData($data);

		$sectionsLimit = $this->model->getFormSectionsLimit();
		$linksLimit = $this->model->getLinksLimit();

		for($sectionIdx = 1; $sectionIdx <= $sectionsLimit; $sectionIdx++) {
			for($linkIdx = 0; $linkIdx < $linksLimit; $linkIdx++) {
				$urlFieldName = $this->generateUrlFieldName($sectionIdx, $linkIdx);
				if (!empty($data[$urlFieldName])) {
					$data[$urlFieldName] = $this->addProtocolToLink($data[$urlFieldName]);
				}
			}
		}

		return $data;
	}
}
?>