<?php

namespace Kpcrudy\Demo\Controllers;

use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Controllers\Admin\Attendances;
use Kpcrudy\Demo\Demo;

/**
 * Public agenda: what a regular user sees.
 */
class Agenda extends BaseController
{
    /**
     * Read-only catalogue of upcoming published events.
     */
    public function index()
    {
        $db = db_connect();
        $me = $this->user();

        // Precomputed once, so that the callbacks do not run a query per row
        $mine = array_column(
            $db->table('kpdemo_attendances')->select('event_id, status')->where('user_id', $me['id'])->get()->getResultArray(),
            'status',
            'event_id',
        );
        $taken = array_column(
            $db->table('kpdemo_attendances')->select('event_id, COUNT(*) AS total')->whereIn('status', ['registered', 'attended'])->groupBy('event_id')->get()->getResultArray(),
            'total',
            'event_id',
        );
        $freePlaces = static fn (array $row): ?int => $row['capacity'] === null ? null : max(0, (int) $row['capacity'] - (int) ($taken[$row['id']] ?? 0));

        $crud = new Crud(new CrudConfig());
        $crud->setTable('kpdemo_events')
            ->setSubject('event', 'Agenda')
            ->where('status', 'published')
            ->where('starts_at >=', date('Y-m-d H:i:s'))
            ->readOnly()
            ->unsetTrash()
            ->unsetBulk()
            ->columns(['poster', 'title', 'category_id', 'starts_at', 'location', 'price', 'places', 'my_status'])
            ->showFields(['poster', 'title', 'category_id', 'summary', 'description', 'starts_at', 'ends_at', 'location', 'price', 'places', 'my_status'])
            ->displayAs([
                'poster'      => 'Poster',
                'title'       => 'Title',
                'category_id' => 'Category',
                'summary'     => 'Summary',
                'description' => 'Description',
                'starts_at'   => 'Date',
                'ends_at'     => 'Ends',
                'location'    => 'Location',
                'price'       => 'Price',
                'places'      => 'Places left',
                'my_status'   => 'My registration',
            ])
            ->setRelation('category_id', 'kpdemo_categories', 'name')
            ->fieldType('poster', 'image', ['path' => Demo::uploadPath('posters'), 'url' => Demo::uploadUrl('posters')])
            ->callbackColumn('price', static fn ($value) => (float) $value > 0 ? '€' . esc(number_format((float) $value, 2)) : 'Free')
            ->callbackColumn('places', static function ($value, array $row) use ($freePlaces) {
                $free = $freePlaces($row);

                return match (true) {
                    $free === null => 'No limit',
                    $free === 0    => '<span class="badge text-bg-danger">Full</span>',
                    default        => (string) $free,
                };
            })
            ->callbackColumn('my_status', static function ($value, array $row) use ($mine) {
                $status = $mine[$row['id']] ?? null;

                return $status === null ? '' : '<span class="badge text-bg-' . Attendances::STATUS_COLORS[$status] . '">' . esc(Attendances::STATUSES[$status]) . '</span>';
            })
            ->addAction('Join', 'kpcrudy-demo/agenda-join/{id}', [
                'icon'    => 'bi bi-person-plus',
                'class'   => 'btn-success',
                'method'  => 'post',
                'confirm' => 'Do you want to register for this event?',
                'visible' => static fn (array $row) => ($mine[$row['id']] ?? 'cancelled') === 'cancelled' && $freePlaces($row) !== 0,
            ])
            ->filters(['category_id', 'starts_at', 'price'])
            ->searchFields(['title', 'summary', 'location', 'category_id'])
            ->defaultOrder('starts_at')
            ->setPerPage(5, [5, 10, 20]);

        return $crud->render();
    }

    /**
     * Registers the logged-in user to an event (row action, POST).
     */
    public function join(int $id)
    {
        $db    = db_connect();
        $me    = $this->user();
        $event = $db->table('kpdemo_events')
            ->where('id', $id)
            ->where('status', 'published')
            ->where('starts_at >=', date('Y-m-d H:i:s'))
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($event === null) {
            return redirect()->back()->with('error', 'This event does not accept registrations.');
        }

        $current = $db->table('kpdemo_attendances')->where('event_id', $id)->where('user_id', $me['id'])->get()->getRowArray();

        if ($current !== null && $current['status'] !== 'cancelled') {
            return redirect()->back()->with('error', 'You are already registered.');
        }

        $taken = $db->table('kpdemo_attendances')->where('event_id', $id)->whereIn('status', ['registered', 'attended'])->countAllResults();

        if ($event['capacity'] !== null && $taken >= (int) $event['capacity']) {
            return redirect()->back()->with('error', 'There are no places left.');
        }

        $now = date('Y-m-d H:i:s');
        $row = ['status' => 'registered', 'registered_at' => $now, 'updated_at' => $now];

        if ($current === null) {
            $db->table('kpdemo_attendances')->insert($row + ['user_id' => $me['id'], 'event_id' => $id, 'created_at' => $now]);
        } else {
            $db->table('kpdemo_attendances')->where('id', $current['id'])->update($row);
        }

        return redirect()->back()->with('message', 'You are now registered for "' . $event['title'] . '".');
    }
}
