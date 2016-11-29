<?php

$w = 300;
$h = 600;

if($w < 64) {
	die("min width is 64\n");
}

$file_data = gzdeflate(file_get_contents($argv[1]), 9);

$magic = chr(0xA5).chr(0xA5);
$width = pack('v', $w);
$height = pack('v', $h);
$data_size = strlen($file_data);

$bits = $w*$h;
if($data_size*8+64 > $bits) {
	die("file is too big to be transferred, split file in chunks or enlarge w and h\n");
}

$data = $magic . $width . $height . $data_size . $file_data;

$image = imagecreatetruecolor($w, $h);

$black = imagecolorallocate($image, 0,0,0);
$white = imagecolorallocate($image, 255,255,255);

for($i = 0; $i < strlen($data); $i++) {
	$bits = sprintf('%08b', ord($data[$i]));
	for($j = 0; $j < 8; $j++) {
		$p = $i*8 + $j;
//		printf("%d,%d ", $p%$w, (int)$p/$h);
		imagesetpixel($image, $p%$w, (int)$p/$w, $bits[$j] === '1' ? $white : $black);
	}
}

imagepng($image);
