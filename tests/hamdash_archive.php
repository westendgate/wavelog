<?php
// Focused integration checks without a Wavelog database or network services.
define('BASEPATH', __DIR__);
function __($text) { return $text; }
function html_escape($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function site_url($path) { return 'https://example.invalid/wavelog/index.php/' . $path; }
function redirect($path) { throw new RuntimeException('redirect:' . $path); }
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
class TestUser {
    public $allowed = true;
    public $level;
    public function authorize($level) { $this->level = $level; return $this->allowed; }
}
class TestSession { public function set_flashdata($key, $value) {} }
class TestLoader {
    public $views = [];
    public function view($name, $data = []) { $this->views[] = [$name, $data]; }
}
class CI_Controller {
    public $user_model;
    public $session;
    public $load;
    public static $allowed = true;
    public function __construct() {
        $this->user_model = new TestUser();
        $this->user_model->allowed = self::$allowed;
        $this->session = new TestSession();
        $this->load = new TestLoader();
    }
}
require __DIR__ . '/../application/controllers/Map.php';

foreach (['propagation' => false, 'propagation_archive' => true] as $method => $archive) {
    $controller = new Map();
    $controller->$method();
    check($controller->user_model->level === 2, 'Existing authorization changed');
    $views = $controller->load->views;
    check(array_column($views, 0) === ['interface_assets/header', 'map/propagation', 'interface_assets/footer'], 'Wrong page composition');
    check($views[1][1]['hamdash_archive'] === $archive, 'Wrong embedded view selected');
    extract($views[1][1]);
    ob_start();
    require __DIR__ . '/../application/views/map/propagation.php';
    $html = ob_get_clean();
    $suffix = $archive ? '?view=archive' : '';
    check(str_contains($html, 'src="https://example.invalid/wavelog/index.php/ham-dash/' . $suffix . '"'), 'Subdirectory URL or archive mode lost');
    check(substr_count($html, 'aria-current="page"') === 1, 'Selected navigation missing or duplicated');
    check(str_contains($html, 'map/propagation_archive'), 'Archive navigation missing');
    check(str_contains($html, 'referrerpolicy="same-origin"'), 'Iframe policy changed');
}
CI_Controller::$allowed = false;
try { new Map(); throw new RuntimeException('Unauthorized user was admitted'); }
catch (RuntimeException $e) { check($e->getMessage() === 'redirect:dashboard', 'Authorization did not redirect'); }
$helper = new ReflectionMethod(Map::class, 'render_hamdash');
check($helper->isPrivate(), 'Internal renderer exposed as a controller route');
echo "Wavelog Ham-Dash live/archive rendering, subdirectory URLs and authorization: OK\n";
