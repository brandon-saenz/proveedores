<?php

require_once(APP . 'plugins/phpqrcode/qrlib.php');

final class Modelos_QR extends Modelo {

	static public function generateQRCodeImage($codeContents, $altura = null) {
		$tempDir = ROOT_DIR . '/data/privada/qr_files/';
		$fileName = 'QR_file_' . md5($codeContents) . '.png';
		$pngAbsoluteFilePath = $tempDir . $fileName;

		QRcode::png($codeContents, $pngAbsoluteFilePath);

		if($altura){
			return '<img style="height:'.$altura.'px;" src="' . $pngAbsoluteFilePath . '" />';
		}else{
			return '<img src="' . $pngAbsoluteFilePath . '" />';
		}
		
	}

	static public function getQRCodeFileName($codeContents) {
	    $tempDir = ROOT_DIR . '/data/privada/qr_files/';
		$fileName = 'QR_file_' . md5($codeContents) . '.png';
		$pngAbsoluteFilePath = $tempDir . $fileName;
		$pngRelativeFilePath = '/devvalcas/data/privada/qr_files/' . $fileName;

		$tamano_pixeles = 0;
		QRcode::png($codeContents, $pngAbsoluteFilePath, QR_ECLEVEL_L, 50, $tamano_pixeles);

		return array(
			'absolute_path' => $pngAbsoluteFilePath,
			'relative_path' => $pngRelativeFilePath
		);
	}
}