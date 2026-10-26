<?php

namespace Kpcrudy\Demo\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Kpcrudy\Demo\Demo;
use Kpcrudy\Demo\Installer;
use Kpcrudy\Demo\Libraries\Auth;
use Throwable;

class Home extends BaseController
{
    /**
     * Dashboard with the demo map: which page shows which feature.
     */
    public function index(): string
    {
        $db = db_connect();
        $me = $this->user();

        $stats = [
            'users'      => $db->table('kpdemo_users')->where('deleted_at', null)->countAllResults(),
            'categories' => $db->table('kpdemo_categories')->countAllResults(),
            'events'     => $db->table('kpdemo_events')->where('deleted_at', null)->where('status', 'published')->where('starts_at >=', date('Y-m-d H:i:s'))->countAllResults(),
            'mine'       => $db->table('kpdemo_attendances')->where('user_id', $me['id'])->whereIn('status', ['registered', 'attended'])->countAllResults(),
        ];

        $upcoming = $db->table('kpdemo_events e')
            ->select('e.id, e.title, e.starts_at, e.location, c.name AS category, c.color')
            ->join('kpdemo_categories c', 'c.id = e.category_id')
            ->where('e.deleted_at', null)
            ->where('e.status', 'published')
            ->where('e.starts_at >=', date('Y-m-d H:i:s'))
            ->orderBy('e.starts_at')
            ->limit(5)
            ->get()
            ->getResultArray();

        return $this->page('Kpcrudy\Demo\Views\home\index', [
            'me'       => $me,
            'isAdmin'  => Auth::isAdmin(),
            'stats'    => $stats,
            'upcoming' => $upcoming,
        ], 'Home');
    }

    /**
     * Drops the demo tables and files and creates them again with the initial data.
     * The route does not exist in production (Demo::canResetFromWeb()).
     */
    public function reset()
    {
        if (! Demo::canResetFromWeb()) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            (new Installer())->reset();
        } catch (Throwable $e) {
            // The demo stays installed: the reset can be tried again
            log_message('error', 'kpcrudy demo reset failed: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(Demo::url())->with('error', 'The demo data could not be reset (see the log). Try again or run "php spark kpcrudy:demo reset".');
        }

        Auth::logout();

        return redirect()->to(Demo::url('login'))->with('message', 'The demo data has been reset. Please log in again.');
    }
}
