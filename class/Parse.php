<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }
/**
 * Класс для обработки и форматирования контента
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Utilities
 * @version    0.9.8
 * 
 * @method string truncateHTML(string $text, int $size = 300, string $finisher = '...') Безопасно обрезает HTML с сохранением тегов
 * @method string userblocks(string $content, array $config, array|null $user = null)    Обрабатывает скрытые блоки для зарегистрированных пользователей
 * @method string time_elapsed_string(string $datetime, bool $full = false)              Форматирует дату в относительный формат
 * @method string getNounPluralForm(int $number, string $one, string $two, string $five) Склоняет существительные по числу
 * @method string getLogBadgeColor(string $action)                                       Определяет цвет метки для логов по типу действия
 */
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
	public static function userblocks($content, $config, $user = null) {
        if (empty($config['blocks_for_reg'])) {
            return $content;
        }

        return preg_replace_callback(
			"#\[hide\](.*?)\[/hide\]#is",
			function($matches) use ($user) {
				if (is_array($user)) {
					return htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
				}
				return '<div class="w3-hide-container w3-round">
  <span class="w3-hide-message">' . 
					   htmlspecialchars(Lang::get('not_logged'), ENT_QUOTES, 'UTF-8') .  
					   '</span>
</div>';
			},
			$content
		);
    }
/**
 * Возвращает строку с временем, прошедшим с указанной даты
 */
public function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    $weeks = floor($diff->d / 7);
    $days = $diff->d % 7;
    
    $string = [
        'y' => ['год', 'года', 'лет'],
        'm' => ['месяц', 'месяца', 'месяцев'],
        'w' => ['неделя', 'недели', 'недель'],
        'd' => ['день', 'дня', 'дней'],
        'h' => ['час', 'часа', 'часов'],
        'i' => ['минута', 'минуты', 'минут'],
        's' => ['секунда', 'секунды', 'секунд'],
    ];
    
    $values = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    ];
    
    $result = [];
    foreach ($string as $k => $v) {
        if ($values[$k] > 0) {
            $n = $values[$k];
            $result[] = $n . ' ' . $this->getNounPluralForm($n, $v[0], $v[1], $v[2]);
        }
    }
    
    if (!$full) {
        $result = array_slice($result, 0, 1);
    }
    
    return $result ? implode(', ', $result) . ' назад' : 'только что';
}

// Функция для правильного склонения существительных
public function getNounPluralForm($number, $one, $two, $five) {
    $number = abs($number) % 100;
    if ($number > 10 && $number < 20) {
        return $five;
    }
    $number %= 10;
    if ($number > 1 && $number < 5) {
        return $two;
    }
    if ($number == 1) {
        return $one;
    }
    return $five;
}
/**
 * Возвращает цвет badge в зависимости от типа действия
 */
public function getLogBadgeColor($action) {
    $action = strtolower($action);
    if (strpos($action, 'удал') !== false) return 'danger';
    if (strpos($action, 'добав') !== false || strpos($action, 'созда') !== false) return 'success';
    if (strpos($action, 'опытк') !== false || strpos($action, 'измен') !== false) return 'warning';
    if (strpos($action, 'ошибка') !== false) return 'danger';
    if (strpos($action, 'вход') !== false || strpos($action, 'выход') !== false) return 'info';
    return 'secondary';
}
}
?>
