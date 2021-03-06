<?php

namespace KayStrobach\Themes\Tca;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render a Content Behaviour row
 *
 * @package KayStrobach\Themes\Tca
 */
class ContentBehaviour extends AbstractContentRow {

	protected $checkboxesArray = array();
	protected $valuesFlipped = array();
	protected $valuesAvailable = array();

	/**
	 * Render a Content Behaviour row
	 *
	 * @param array $parameters
	 * @param mixed $parentObject
	 * @return string
	 */
	public function renderField(array &$parameters, &$parentObject) {

		// Vars
		$uid   = $parameters['row']['uid'];
		$pid   = $parameters['row']['pid'];
		$name  = $parameters['itemFormElName'];
		$value = $parameters['itemFormElValue'];
		$cType = $parameters['row']['CType'];
		$gridLayout = $parameters['row']['tx_gridelements_backend_layout'];

		// Get values
		$values = explode(',', $value);
		$this->valuesFlipped = array_flip($values);
		$this->valuesAvailable = array();

		// Get configuration
		$behaviours = $this->getMergedConfiguration($pid, 'behaviour', $cType);

		// Build checkboxes
		$this->checkboxesArray['default'] = array();
		$this->checkboxesArray['ctype'] = array();
		$this->checkboxesArray['gridLayout'] = array();
		if (isset($behaviours['properties']) && is_array($behaviours['properties'])) {

			foreach ($behaviours['properties'] as $contentElementKey => $label) {

				// GridElements: are able to provide grid-specific behaviours
				if (is_array($label) && $cType === 'gridelements_pi1') {
					$contentElementKey = substr($contentElementKey, 0, -1);

					// Behaviour for all GridElements
					if ($contentElementKey == 'default' && !empty($label)) {
						foreach ($label as $gridLayoutKey => $gridLayoutBehaviourLabel) {
							$this->createCheckbox($gridLayoutKey, $gridLayoutBehaviourLabel, 'ctype');
						}
					}
					// Behaviour only for selected GridElement
					else if ($contentElementKey == $gridLayout && !empty($label)) {
						foreach ($label as $gridLayoutKey => $gridLayoutBehaviourLabel) {
							$this->createCheckbox($gridLayoutKey, $gridLayoutBehaviourLabel, 'gridLayout');
						}
					}

				}
				// Normal CEs
				else {
					// Is default property!?
					if (array_key_exists($contentElementKey, $this->defaultProperties)) {
						$this->createCheckbox($contentElementKey, $label, 'default');
					}
					// Is ctype specific!
					else {
						$this->createCheckbox($contentElementKey, $label, 'ctype');
					}
				}

			}
		}

		// Merge checkbox groups
		$checkboxes = '';
		$checkboxes .= $this->getMergedCheckboxes('default');
		$checkboxes .= $this->getMergedCheckboxes('ctype');
		$checkboxes .= $this->getMergedCheckboxes('gridLayout');
		if ($checkboxes === '') {
			$checkboxes = $GLOBALS['LANG']->sL('LLL:EXT:themes/Resources/Private/Language/locallang.xlf:behaviour.no_behaviour_available');
		}

		/**
		 * Include jQuery in backend
		 * @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
		 */
		$pageRenderer = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
		$pageRenderer->loadJquery(NULL, NULL, $pageRenderer::JQUERY_NAMESPACE_DEFAULT_NOCONFLICT);

		/**
		 * @todo auslagern!!
		 */
		$script = '<script type="text/javascript">' . LF;
		$script .= 'function contentBehaviourChange(field) {' . LF;
		$script .= 'var itemselector = "";' . LF;
		$script .= 'if(jQuery(field).closest(".t3-form-field-item").index() > 0){' . LF;
		$script .= '  itemselector = ".t3-form-field-item";' . LF;
		$script .= '}else if(jQuery(field).closest(".t3js-formengine-field-item").index() > 0){' . LF;
		$script .= 'itemselector = ".t3js-formengine-field-item";}' . LF;
		$script .= '  if (field.checked) {' . LF;
		$script .= '    jQuery(field).closest(itemselector).find(".contentBehaviour input[readonly=\'readonly\']").addClass(field.name);' . LF;
		$script .= '  }' . LF;
		$script .= '  else {' . LF;
		$script .= '    jQuery(field).closest(itemselector).find(".contentBehaviour input[readonly=\'readonly\']").removeClass(field.name);' . LF;
		$script .= '  }' . LF;
		$script .= '  jQuery(field).closest(itemselector).find(".contentBehaviour input[readonly=\'readonly\']").attr("value", ' . LF;
		$script .= '  jQuery(field).closest(itemselector).find(".contentBehaviour input[readonly=\'readonly\']").attr("class").replace(/\ /g, ","));' . LF;
		$script .= '}' . LF;
		$script .= '</script>' . LF;

		$setClasses = array_intersect($values, $this->valuesAvailable);
		$setClass = htmlspecialchars(implode(' ', $setClasses));
		$setValue = htmlspecialchars(implode(',', $setClasses));

		$inputType = 'hidden';
		if($this->isAdmin()) {
			$inputType = 'text';
		}
		$hiddenField = '<input style="width:90%;background-color:#dadada" readonly="readonly" type="' . $inputType . '" name="' . htmlspecialchars($name) . '" value="' . $setValue . '" class="' . $setClass . '">' . LF;

		// Missed classes
		$missedField = $this->getMissedFields($values, $this->valuesAvailable);

		return '<div class="contentBehaviour">' . $checkboxes . $hiddenField . $script . $missedField . '</div>';
	}

	/**
	 * Creates a checkbox
	 *
	 * @param $key \string Key/name of the checkbox
	 * @param $label \string Label of the checkbox
	 * @param $type \string Type of the checkbox property
	 */
	protected function createCheckbox($key, $label, $type) {
		$label = $GLOBALS['LANG']->sL($label);
		$labelStyles = 'display: inline-block;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;width: 190px';
		$this->valuesAvailable[] = $key;
		$checked = (isset($this->valuesFlipped[$key])) ? 'checked="checked"' : '';
		$checkbox = '<div style="width:200px;float:left">' . LF;
		$checkbox .= '<label style="' . $labelStyles . '" title="' . $label . '">' . LF;
		$checkbox .= '<input type="checkbox" onchange="contentBehaviourChange(this)" name="' . $key . '" ' . $checked . '>' . LF;
		$checkbox .= $label . '</label>' . LF;
		$checkbox .= '</div>' . LF;
		$this->checkboxesArray[$type][] = $checkbox;
	}

	/**
	 * Merge checkboxes into a group
	 *
	 * @param $type \string Type of the checkbox property
	 * @return string Grouped checkboxes
	 */
	protected function getMergedCheckboxes($type) {
		$checkboxes = '';
		if (!empty($this->checkboxesArray[$type])) {
			$labelKey = 'LLL:EXT:themes/Resources/Private/Language/locallang.xlf:behaviour.' . strtolower($type) . '_group_label';
			$label = $GLOBALS['LANG']->sL($labelKey);
			$checkboxes .= '<fieldset style="border:0 solid">' . LF;
			$checkboxes .= '<legend style="font-weight:bold">' . $label . ':</legend>' . implode('', $this->checkboxesArray[$type]). LF;
			$checkboxes .= '</fieldset>' . LF;
		}
		return $checkboxes;
	}
}