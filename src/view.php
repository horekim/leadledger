<?php
/** The view layer: plain PHP templates wrapped in the shared layout. */

function view_path(string $name): string
{
    return dirname(__DIR__) . '/views/' . $name . '.php';
}

/** Include a template with $vars in scope. */
function view(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require view_path($name);
}

/**
 * Render a page template inside views/layout.php.
 *
 * $vars['title']  — browser tab
 * $vars['screen'] — 'browse' | 'admin' | 'auth', drives the header's context button
 */
function render(string $name, array $vars = []): void
{
    ob_start();
    view($name, $vars);
    $content = ob_get_clean();

    view('layout', $vars + [
        'content' => $content,
        'title'   => '',
        'screen'  => 'browse',
    ]);
}

/** One-shot messages carried across a redirect. */
function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $held = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $held;
}
