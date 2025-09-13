<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }
/**
 * Класс для работы с пагинацией
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Utilities
 * @version    0.9.8
 * 
 * @method static array  calculate(int $totalItems, string $type, int $currentPage = 1, array $config) Рассчитывает параметры пагинации
 * @method static string render(array $paginationData, string $baseUrl, string $pageParam = 'page')    Генерирует HTML-код пагинации
 */
class Pagination {
    const TYPE_NEWS = 'news';
    const TYPE_COMMENTS = 'comments';
    /**
     * Рассчитывает данные пагинации
     * 
     * @param int $totalItems Общее количество элементов
     * @param int $perPage Количество элементов на странице
     * @param int $currentPage Текущая страница
     * @return array Массив с данными пагинации
     */
    public static function calculate(int $totalItems, string $type, int $currentPage = 1, array $config): array {
        // Определяем количество элементов на странице из конфига
        switch ($type) {
            case self::TYPE_NEWS:
                $perPage = (int)($config['blogs_per_page'] ?? 6);
                break;
            case self::TYPE_COMMENTS:
                $perPage = (int)($config['comments_per_page'] ?? 3);
                break;
            default:
                $perPage = 10;
        }
        
        $totalPages = (int)max(1, ceil($totalItems / $perPage));
        $currentPage = (int)max(1, min($currentPage, $totalPages));
        
        return [
            'total_items' => $totalItems,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'offset' => ($currentPage - 1) * $perPage
        ];
    }

    /**
     * Генерирует HTML пагинации
     * 
     * @param array $paginationData Данные пагинации из метода calculate()
     * @param string $baseUrl Базовый URL для ссылок
     * @param string $pageParam Имя параметра страницы в URL
     * @return string HTML код пагинации
     */
    public static function render(array $paginationData, string $baseUrl, string $pageParam = 'page'): string {
        $currentPage = $paginationData['current_page'];
        $totalPages = $paginationData['total_pages'];
        
        if ($totalPages <= 1) {
            return '';
        }
        
        // Параметры URL
		// Разбираем базовый URL
		$urlParts = parse_url($baseUrl);
		$path = $urlParts['path'] ?? '';
		parse_str($urlParts['query'] ?? '', $queryParams);
		
		// Удаляем дублирующиеся параметры
		if (isset($queryParams[$pageParam])) {
			unset($queryParams[$pageParam]);
		}
        
        
        $links = [];
        
        // Кнопка "Назад"
        if ($currentPage > 1) {
            $queryParams[$pageParam] = $currentPage - 1;
            $prevUrl = $urlPath . '?' . http_build_query($queryParams);
            $links[] = sprintf(
                '<a href="%s" class="w3-button w3-black w3-padding-large w3-margin-bottom">%s</a>',
                htmlspecialchars($prevUrl),
                Lang::get('prev')
            );
        } else {
            $links[] = sprintf(
                '<button class="w3-button w3-black w3-disabled w3-padding-large w3-margin-bottom">%s</button>',
                Lang::get('prev')
            );
        }
        
        // Номера страниц
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $queryParams[$pageParam] = $i;
            $pageUrl = $urlPath . '?' . http_build_query($queryParams);
            
            if ($i == $currentPage) {
                $links[] = sprintf(
                    '<span class="w3-button w3-gray w3-padding-large w3-margin-bottom">%d</span>',
                    $i
                );
            } else {
                $links[] = sprintf(
                    '<a href="%s" class="w3-button w3-black w3-padding-large w3-margin-bottom">%d</a>',
                    htmlspecialchars($pageUrl),
                    $i
                );
            }
        }
        
        // Кнопка "Вперед"
        if ($currentPage < $totalPages) {
            $queryParams[$pageParam] = $currentPage + 1;
            $nextUrl = $urlPath . '?' . http_build_query($queryParams);
            $links[] = sprintf(
                '<a href="%s" class="w3-button w3-black w3-padding-large w3-margin-bottom">%s &raquo;</a>',
                htmlspecialchars($nextUrl),
                Lang::get('next')
            );
        } else {
            $links[] = sprintf(
                '<button class="w3-button w3-black w3-disabled w3-padding-large w3-margin-bottom">%s &raquo;</button>',
                Lang::get('next')
            );
        }
        
        return '<div class="pagination">' . implode('', $links) . '</div>';
		
    
    }
}