<?php
/**
 * Lead Ledger — front controller.
 *
 *   /                          the sets index
 *   /{range-slug}/{set-code}   a set's photo grid
 *   /sign-in                   sign in / create account
 *   /admin                     ranges & sets
 *   /admin/sets/{id}           a set's miniatures
 *   /api/…                     the calls app.js makes
 */

require __DIR__ . '/src/bootstrap.php';

set_exception_handler(static function (Throwable $ex): void {
    if ($ex instanceof PDOException) {
        db_unavailable($ex);
    }
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo config('debug') ? (string)$ex : "Something went wrong.\n";
});

auth_boot();

/* — where are we? — */

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// index.php/sign-in — the front controller was addressed directly, so the route
// is in PATH_INFO and no rewriting was involved.
$pathInfo = $_SERVER['PATH_INFO'] ?? '';

if ($pathInfo !== '') {
    $path = trim(rawurldecode($pathInfo), '/');
} else {
    $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_url();

    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    // A request straight to /leadledger/index.php has no route of its own.
    $path = trim(rawurldecode($uri), '/');
    if ($path === 'index.php') {
        $path = '';
    }
}

$seg = $path === '' ? [] : explode('/', $path);

/** Density is remembered for the session, per the design. */
function density(): string
{
    $allowed = ['contact', 'compact', 'comfortable'];
    $asked   = $_GET['density'] ?? null;
    if (is_string($asked) && in_array($asked, $allowed, true)) {
        $_SESSION['density'] = $asked;
    }
    return $_SESSION['density'] ?? 'compact';
}

/** Send a form submission back where it came from, same-site only. */
function back_to(string $fallback = ''): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        $parts = parse_url($ref);
        $host  = $parts['host'] ?? '';
        if ($host === '' || $host === ($_SERVER['HTTP_HOST'] ?? '')) {
            $target = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
            header('Location: ' . $target, true, 302);
            exit;
        }
    }
    redirect($fallback);
}

function not_found(): void
{
    http_response_code(404);
    render('error', [
        'title'   => 'Not found',
        'heading' => 'Nothing here',
        'body'    => 'That range or set is not in the archive.',
    ]);
    exit;
}

/* — routing — */

// POST|PUT|DELETE /api/wanted/{miniatureId}   idempotent wanted toggle
if (count($seg) === 3 && $seg[0] === 'api' && $seg[1] === 'wanted' && ctype_digit($seg[2])) {
    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        json_fail('Use POST, PUT or DELETE.', 405);
    }
    csrf_guard();
    require_admin(); // one hunt, and only an admin edits it

    $miniId = (int)$seg[2];
    $mini   = repo_miniature($miniId);
    if (!$mini) {
        json_fail('No such miniature.', 404);
    }

    if ($method === 'PUT') {
        $wanted = true;
    } elseif ($method === 'DELETE') {
        $wanted = false;
    } else {
        $wanted = (string)($_POST['wanted'] ?? json_body()['wanted'] ?? '1') === '1';
    }

    repo_set_wanted($miniId, $wanted);

    if (is_json_request()) {
        json_out([
            'ok'           => true,
            'wanted'       => $wanted,
            'wanted_count' => repo_wanted_count_in_set((int)$mini['set_id']),
        ]);
    }
    back_to();
}

// POST|PUT|DELETE /api/collection/{miniatureId}   idempotent owned toggle
if (count($seg) === 3 && $seg[0] === 'api' && $seg[1] === 'collection' && ctype_digit($seg[2])) {
    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        json_fail('Use POST, PUT or DELETE.', 405);
    }
    csrf_guard();
    require_admin(); // one collection, and only an admin edits it

    $miniId = (int)$seg[2];
    $mini   = repo_miniature($miniId);
    if (!$mini) {
        json_fail('No such miniature.', 404);
    }

    // PUT means own it, DELETE means release it; POST carries the wanted state.
    if ($method === 'PUT') {
        $wanted = true;
    } elseif ($method === 'DELETE') {
        $wanted = false;
    } else {
        $wanted = (string)($_POST['owned'] ?? json_body()['owned'] ?? '1') === '1';
    }

    repo_set_owned($miniId, $wanted);

    // Owning something ends the hunt — the wanted row goes rather than lying
    // dormant. Un-ticking later does not resurrect it.
    if ($wanted) {
        repo_set_wanted($miniId, false);
    }

    if (is_json_request()) {
        $setId = (int)$mini['set_id'];
        json_out([
            'ok'           => true,
            'owned'        => $wanted,
            'owned_count'  => repo_owned_count_in_set($setId),
            'wanted_count' => repo_wanted_count_in_set($setId),
            'total'        => repo_count_in_set($setId),
        ]);
    }
    back_to();
}

// POST /admin/sets/{id}/reorder   persist a drag-reorder
if (count($seg) === 4 && $seg[0] === 'admin' && $seg[1] === 'sets' && ctype_digit($seg[2]) && $seg[3] === 'reorder') {
    csrf_guard();
    require_admin();
    if ($method !== 'POST') {
        json_fail('Use POST.', 405);
    }

    $setId = (int)$seg[2];
    if (!repo_set($setId)) {
        json_fail('No such set.', 404);
    }

    $ids = json_body()['order'] ?? [];
    if (!is_array($ids) || !$ids) {
        json_fail('Send the miniature ids in their new order.');
    }
    repo_reorder_miniatures($setId, array_map('intval', $ids));
    json_out(['ok' => true]);
}

// GET|POST /sign-in
if ($seg === ['sign-in']) {
    if (is_signed_in() && $method === 'GET') {
        redirect('');
    }

    $mode  = ($_GET['mode'] ?? $_POST['mode'] ?? 'in') === 'up' ? 'up' : 'in';
    if ($mode === 'up' && !auth_accepts_signup()) {
        $mode = 'in'; // the owner account already exists
    }
    $error = '';
    $old   = [];

    if ($method === 'POST') {
        csrf_guard();
        $email    = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $remember = ($_POST['remember'] ?? '') === '1';
        $old      = ['name' => (string)($_POST['name'] ?? ''), 'email' => $email];

        [$ok, $error] = $mode === 'up'
            ? auth_register((string)($_POST['name'] ?? ''), $email, $password, $remember)
            : auth_login($email, $password, $remember);


        if ($ok) {
            redirect('');
        }
    }

    render('auth', ['title' => $mode === 'up' ? 'Create an account' : 'Sign in',
                    'screen' => 'auth', 'mode' => $mode, 'error' => $error, 'old' => $old]);
    exit;
}

// POST /sign-out
if ($seg === ['sign-out']) {
    csrf_guard();
    auth_logout();
    redirect('');
}

/* — admin — */

if (($seg[0] ?? '') === 'admin') {
    require_admin();

    // GET /admin
    if (count($seg) === 1) {
        render('admin/index', [
            'title'     => 'Ranges & sets',
            'screen'    => 'admin',
            'catalogue' => repo_catalogue(),
        ]);
        exit;
    }

    // GET /admin/ranges/{id}
    if (count($seg) === 3 && $seg[1] === 'ranges' && ctype_digit($seg[2])) {
        $catalogue = repo_catalogue();
        $rangeId   = (int)$seg[2];

        $range = null;
        foreach ($catalogue as $candidate) {
            if ($candidate['id'] === $rangeId) {
                $range = $candidate;
                break;
            }
        }
        if (!$range) {
            not_found();
        }

        render('admin/range', [
            'title'     => $range['name'],
            'screen'    => 'admin',
            'catalogue' => $catalogue,
            'range'     => $range,
        ]);
        exit;
    }

    // GET /admin/sets/{id}
    if (count($seg) === 3 && $seg[1] === 'sets' && ctype_digit($seg[2])) {
        $set = repo_set((int)$seg[2]);
        if (!$set) {
            not_found();
        }
        render('admin/set', [
            'title'      => $set['code'] . ' ' . $set['name'],
            'screen'     => 'admin',
            'catalogue'  => repo_catalogue(),
            'set'        => $set,
            'miniatures' => repo_miniatures((int)$set['id']),
        ]);
        exit;
    }

    // POST /admin/save — create or update a range or a set
    if ($seg === ['admin', 'save'] && $method === 'POST') {
        csrf_guard();

        $kind     = (string)($_POST['kind'] ?? '');
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim((string)($_POST['name'] ?? ''));
        $code     = trim((string)($_POST['code'] ?? ''));
        $category = (string)($_POST['category'] ?? '');

        if ($name === '') {
            flash('A name is required.');
            back_to('admin');
        }

        if ($kind === 'range') {
            if ($id > 0) {
                // An absent category keeps the one the range already has.
                $existing = repo_range($id);
                if (!category_valid($category)) {
                    $category = $existing ? (string)$existing['category'] : LL_DEFAULT_CATEGORY;
                }
                repo_update_range($id, $name, $category);
                flash('Range saved.');
                back_to('admin/ranges/' . $id);
            }
            $newId = repo_create_range($name, $category);
            flash('Range created.');
            redirect('admin/ranges/' . $newId);
        }

        if ($kind === 'set') {
            if ($code === '') {
                flash('A set needs a code.');
                back_to('admin');
            }
            $before  = $id > 0 ? repo_set($id) : null;
            $rangeId = (int)($_POST['range_id'] ?? 0);

            // Never move a set on a missing value — an absent range means the
            // form did not offer one, not that it belongs in the first range.
            if ($rangeId === 0 && $before) {
                $rangeId = (int)$before['range_id'];
            }
            if (!repo_range($rangeId)) {
                flash('That range no longer exists.');
                back_to('admin');
            }

            try {
                if ($id > 0) {
                    repo_update_set($id, $rangeId, $code, $name);
                    flash($before && (int)$before['range_id'] !== $rangeId ? 'Set moved.' : 'Set saved.');
                } else {
                    repo_create_set($rangeId, $code, $name);
                    flash('Set created.');
                }
            } catch (PDOException $ex) {
                // Codes may repeat within a range, so nothing here is expected
                // to collide — report it rather than swallowing it.
                flash(config('debug') ? $ex->getMessage() : 'That set could not be saved.');
                back_to('admin');
            }

            // A moved set is no longer on the page you edited it from, so go
            // where it now lives rather than back to a list without it.
            if ($before && (int)$before['range_id'] !== $rangeId) {
                redirect('admin/ranges/' . $rangeId);
            }
            back_to('admin');
        }

        flash('Nothing to save.');
        back_to('admin');
    }

    // POST /admin/miniature/save
    if ($seg === ['admin', 'miniature', 'save'] && $method === 'POST') {
        csrf_guard();

        $id    = (int)($_POST['id'] ?? 0);
        $setId = (int)($_POST['set_id'] ?? 0);
        $code  = trim((string)($_POST['code'] ?? ''));
        $name  = trim((string)($_POST['name'] ?? ''));

        if (!repo_set($setId)) {
            not_found();
        }

        // The bulk uploader posts one file per request and wants JSON back;
        // the drawer posts a form and wants to land back on the set page.
        $fail = static function (string $message) use ($setId): void {
            if (is_json_request()) {
                json_fail($message);
            }
            flash($message);
            back_to('admin/sets/' . $setId);
        };

        $existing = $id > 0 ? repo_miniature($id) : null;
        $photo    = $existing['photo'] ?? null;
        $dropped  = null; // the old file, deleted only once the save succeeds

        if (($_POST['remove_photo'] ?? '0') === '1') {
            $dropped = $photo;
            $photo   = null;
        }

        if (!empty($_FILES['photo']['name'])) {
            [$stored, $err] = upload_photo($_FILES['photo']);
            if ($stored === null) {
                $fail($err);
            }
            $dropped = $existing['photo'] ?? null;
            $photo   = $stored;
        }

        // A miniature is its photograph. The code and name are optional.
        if ($photo === null) {
            $fail($existing
                ? 'A miniature needs a photograph — choose a replacement before saving.'
                : 'A miniature needs a photograph.');
        }

        if ($existing) {
            repo_update_miniature($id, $code, $name, $photo);
            $created = $id;
            flash('Miniature saved.');
        } else {
            $created = repo_create_miniature($setId, $code, $name, $photo);
            flash('Miniature added.');
        }

        if ($dropped !== null && $dropped !== $photo) {
            upload_delete($dropped);
        }

        if (is_json_request()) {
            json_out(['ok' => true, 'id' => $created, 'photo' => asset($photo)]);
        }
        redirect('admin/sets/' . $setId);
    }

    // POST /admin/delete
    if ($seg === ['admin', 'delete'] && $method === 'POST') {
        csrf_guard();

        $kind = (string)($_POST['kind'] ?? '');
        $id   = (int)($_POST['id'] ?? 0);

        if ($kind === 'range' && $id > 0) {
            repo_delete_range($id);
            flash('Range deleted.');
            redirect('admin');
        }
        if ($kind === 'set' && $id > 0) {
            $set = repo_set($id);
            repo_delete_set($id);
            flash('Set deleted.');
            redirect($set ? 'admin/ranges/' . (int)$set['range_id'] : 'admin');
        }
        if ($kind === 'miniature' && $id > 0) {
            $mini = repo_miniature($id);
            if ($mini) {
                upload_delete($mini['photo']);
                repo_delete_miniature($id);
                flash('Miniature deleted.');
                redirect('admin/sets/' . (int)$mini['set_id']);
            }
        }
        redirect('admin');
    }

    not_found();
}

/* — public — */

// GET /
if ($seg === []) {
    $catalogue = repo_catalogue();
    render('public/index', [
        'title'     => '’Eavy Metal',
        'catalogue' => $catalogue,
        'totals'    => repo_totals($catalogue),
    ]);
    exit;
}

// GET /{range-slug}/{set-code}
if (count($seg) === 2) {
    $set = repo_set_by_slugs($seg[0], $seg[1]);
    if (!$set) {
        not_found();
    }

    $filter = $_GET['show'] ?? 'all';
    if (!in_array($filter, ['all', 'owned', 'missing', 'wanted'], true)) {
        $filter = 'all';
    }

    render('public/set', [
        'title'      => $set['name'],
        'catalogue'  => repo_catalogue(),
        'range'      => ['id' => (int)$set['range_id'], 'name' => $set['range_name'], 'slug' => $set['range_slug']],
        'set'        => $set,
        'miniatures' => repo_miniatures((int)$set['id']),
        'filter'     => $filter,
        'density'    => density(),
    ]);
    exit;
}

not_found();
