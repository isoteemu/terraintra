<?php

/**
 * VCard presentation.
 * Depends of File_IMC from Pear
 * @see http://pear.php.net/package/File_IMC
 */

class Intra_View_VCard extends Intra_View {

	// Internal presentation for vCard
	protected $vCard;

	public function init() {
		require_once('File/IMC.php');
		$this->vCard = File_IMC::build('vCard');
	}

	/**
	 * Create base64 presentation of image.
	 * @param $file String
	 *   File to attach.
	 * @return Array
	 *   Assocative array if successfull, where:
	 *    - type is image type,
	 *    - encoding is image encoding type
	 *    - data is encoded image
	 */
	protected function _image($file) {
		if($file instanceof Intra_View_Plugin_Image_Tag)
			$file = (string) $file['src'];

		// Imagetypes, can be something else too but too lazy to add anything.
		$imagetypes = array(
			IMAGETYPE_JPEG => 'JPEG',
			IMAGETYPE_GIF  => 'GIF',
			IMAGETYPE_PNG  => 'PNG',
			IMAGETYPE_BMP  => 'BMP'
		);

		if(!($image = @getimagesize($file)))
			throw new InvalidArgumentException('Could not retrieve image file '.$file.' information.');

		if(!isset($imagetypes[$image[2]]))
			throw new InvalidArgumentException('Image type '.$image[2].' is unknown.');

		$r = array(
			'type' => $imagetypes[$image[2]],
			'encoding' => 'B',
			'data' => base64_encode(file_get_contents($file))
		);
		return $r;
	}

	public function __toString() {
		return $this->vCard->fetch();
	}

}
