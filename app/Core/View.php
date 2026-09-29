<?php
namespace App\Core;

defined('APP') or exit;

/** Renderização de views com layout. */
class View {
    public static function render($view, array $data = [], $layout = null) {
        $file = APP_ROOT . '/views/' . $view . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'View não encontrada: ' . e($view);
            exit;
        }
        extract($data, EXTR_SKIP);
        if ($layout === null) {
            require $file;
            return;
        }
        ob_start();
        require $file;
        $__content = ob_get_clean();
        require APP_ROOT . '/views/' . $layout . '.php';
    }

    public static function partial($view, array $data = []) {
        $file = APP_ROOT . '/views/' . $view . '.php';
        if (!is_file($file)) return '';
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean();
    }
}
