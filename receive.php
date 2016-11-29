<?php

function read_byte($image, $x, $y)
{
	$bits = '';
	for($i = 0; $i < 8; $i++) {
		$bits[$i] = imagecolorat($image, $x+$i, $y) & 0xFF > 128 ? '1' : '0';
	}

	return chr(bindec(implode('', $bits)));
}

function check_magic($image, $x, $y)
{
	return read_byte($image, $x, $y) === chr(0xA5) && read_byte($image, $x+8, $y) === chr(0xA5);
}

function decode_image($image, $x, $y)
{
	if(!check_magic($image, $x, $y)) {
		return false;
	}
	//printf("magic ok\n");
	$w = unpack('v', read_byte($image, $x+16, $y) . read_byte($image, $x+24, $y));
	$w = $w[1];
	if($w + $x > imagesx($image)) {
		return false;
	}
	//printf("width ok: %d\n", $w);
	$h = unpack('v', read_byte($image, $x+32, $y) . read_byte($image, $x+40, $y));
	$h = $h[1];
	if($h + $y > imagesy($image)) {
		return false;
	}
	//printf("height ok: %d\n", $h);
	$s = unpack('v', read_byte($image, $x+48, $y) . read_byte($image, $x+56, $y));
	$s = $s[1];
	if($s > ($w*$h - 64)) {
		return false;
	}
	//printf("data size ok: %d\n", $s);
	//printf("ready to decode\n");

	$data = '';
	$max_y = $y+$h;
	$max_x = $x+$w;
	$i = 0;
	$byte = '';
	for($y1 = $y; $y1 < $max_y; $y1++) {
		for($x1 = $x; $x1 < $max_x; $x1++) {
			$byte[$i++] = imagecolorat($image, $x1, $y1) & 0xFF > 128 ? '1' : '0';
			if($i >= 8) {
				$data .= chr(bindec(implode('', $byte)));
				if((strlen($data)+8) >= $s) {
					$data = @gzinflate(substr($data, 9));
					return $data;
				}
				$i = 0;
				$byte = '';
			}
		}
	}
	return false;
}

$image = imagecreatefrompng($argv[1]);

$full_w = imagesx($image) - 48;
$full_h = imagesy($image);

for($y = 0; $y < $full_h; $y++) {
	for($x = 0; $x < $full_w; $x++) {
		$rgb = imagecolorat($image, $x, $y);
		$r = ($rgb >> 16) & 0xFF;
		if(($g = ($rgb >> 8) & 0xFF) !== $r) {
			continue;
		}
		if(($b = ($rgb & 0xFF)) !== $r) {
			continue;
		}
		if($r !== 255) {
			continue;
		}
		if(($data = decode_image($image, $x, $y)) !== false) {
			die($data);
		}
	}
}
