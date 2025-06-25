<?php
class parse {
    function truncateHTML($text, $size = 300, $finisher = '...') {
		$len = strlen($text);

		if ($len <= $size) {
			return $text;
		}

		$textLen = 0;
		$position = -1;
		$tagNameStartPos = 0;
		$openTagList = [];

		// Stateful machine status
		// 0 - scanning text
		// 1 - scanning tag name
		// 2 - scanning tag content
		// 3 - scanning tag attribute value
		// 4 - waiting for tag close mark
		$state = 0;

		// 0 - no quotes active
		// 1 - single quotes active
		// 2 - double quotes active
		$quoteType = 0;

		while ((($position + 1) < $len) && ($textLen < $size)) {
			$position++;
			// Используем современный синтаксис доступа к символам строки
			$char = $text[$position];

			switch ($state) {
				// Scanning text
				case 0:
					if ($char == '<') {
						$state = 1;
						$tagNameStartPos = $position + 1;
						continue 2;
					}
					$textLen++;
					break;

				case 1:
					if (($char == ' ') || ($char == "\t")) {
						$state = 2;
						continue 2;
					}

					if ($char == '/') {
						if ($tagNameStartPos == $position) {
							continue 2;
						}
						$state = 4;
						continue 2;
					}

					if ($char == '>') {
						$tagName = substr($text, $tagNameStartPos, $position - $tagNameStartPos);
						
						// Closing tag
						if ($tagName[0] == '/') {
							if ((count($openTagList)) && ($openTagList[count($openTagList) - 1] == substr($tagName, 1))) {
								array_pop($openTagList);
							}
						} else {
							// Opening tag
							if (substr($tagName, -1, 1) != '/') {
								array_push($openTagList, $tagName);
							}
						}
						$state = 0;
						continue 2;
					}

					if (!ctype_alpha($char)) {
						$state = 0;
						continue 2;
					}
					break;

				case 2:
					if ($char == '/') {
						$state = 4;
						continue 2;
					}

					if ($char == '>') {
						$tagName = substr($text, $tagNameStartPos, $position - $tagNameStartPos);
						
						if ((count($openTagList)) && ($openTagList[count($openTagList) - 1] == substr($tagName, 1))) {
							array_pop($openTagList);
						} else {
							if (substr($tagName, -1, 1) != '/') {
								array_push($openTagList, $tagName);
							}
						}
						$state = 0;
						continue 2;
					}

					if (($char == '"') || ($char == "'")) {
						$quoteType = ($char == '"') ? 2 : 1;
						$state = 3;
						continue 2;
					}
					break;

				case 3:
					if ((($char == '"') && ($quoteType == 2)) || (($char == "'") && ($quoteType == 1))) {
						$state = 2;
						continue 2;
					}
					break;

				case 4:
					if (($char == ' ') || ($char == "\t")) {
						continue 2;
					}

					if ($char == '>') {
						$tagName = substr($text, $tagNameStartPos, $position - $tagNameStartPos);
						
						if ($tagName[0] != '/') {
							if ((count($openTagList)) && ($openTagList[count($openTagList) - 1] == substr($tagName, 1))) {
								array_pop($openTagList);
							}
						} else {
							if (substr($tagName, -1, 1) != '/') {
								array_push($openTagList, $tagName);
							}
						}
						$state = 0;
						continue 2;
					}

					$state = 0;
					break;
			}
		}

		$output = substr($text, 0, $position + 1) . ((($position + 1) != $len) ? $finisher : '');

		// Close any remaining open tags
		while ($tag = array_pop($openTagList)) {
			$output .= "</" . $tag . ">";
		}

		return $output;
	}
}
?>
