<?php

/**
 * Description of HTMLMultiSelectPlusAdd
 *
 * @author Sebastian Ulbricht <x_lilu_x@gmx.de>
 */
class HTMLMultiSelectPlusAdd extends HTMLMultiSelectEx {
	protected $allowAdditions = true;

	public function validate( $value, $alldata ) {
		if ( !is_array( $value ) ) {
			return false;
		}

		return true;
	}

	public function getOOUIAttributes() {
		$attr = parent::getOOUIAttributes();

		$attr['allowArbitrary'] = true;

		return $attr;
	}

	public function getInputHTML( $value ) {
		$html = $this->formatOptions( $this->mParams['options'], $value, 'multiselectplusadd' );

		$attrs = [
			'type' => 'button',
			'id' => $this->mName . '-add',
			'title' => wfMessage( 'bs-extjs-add' )->text(),
			'msg' => '',
			'targetField' => $this->mName,
			'class' => 'bsMultiSelectAddButton',
			'onclick' => 'bs.util.addEntryToMultiSelect(this);',
			'value' => '+'
		];
		$button = Html::element( 'input', $attrs );

		$attrs = [
			'type' => 'button',
			'id' => $this->mName . '-delete',
			'targetField' => $this->mName,
			'class' => 'bsMultiSelectDeleteButton',
			'onclick' => 'bs.util.deleteEntryFromMultiSelect(this);',
			'value' => '-'
		];
		$button .= Html::element( 'input', $attrs );

		return $html . '<div>' . $button . '</div>';
	}

}
