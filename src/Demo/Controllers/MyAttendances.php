<?php

namespace Kpcrudy\Demo\Controllers;

use CodeIgniter\Database\BaseBuilder;
use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Controllers\Admin\Attendances;

/**
 * The logged-in user's own registrations.
 */
class MyAttendances extends BaseController
{
    /**
     * Same table as the admin attendance CRUD, restricted to the current user.
     */
    public function index()
    {
        $me       = $this->user();
        $starts   = array_column(
            db_connect()->table('kpdemo_events e')->select('e.id, e.starts_at')
                ->join('kpdemo_attendances a', 'a.event_id = e.id')
                ->where('a.user_id', $me['id'])
                ->get()->getResultArray(),
            'starts_at',
            'id',
        );
        $isFuture = static fn (array $row): bool => isset($starts[$row['event_id']]) && $starts[$row['event_id']] >= date('Y-m-d H:i:s');

        $crud = new Crud(new CrudConfig());
        $crud->setTable('kpdemo_attendances')
            ->setSubject('registration', 'My registrations')
            ->where('user_id', $me['id'])
            ->columns(['event_id', 'event_date', 'status', 'registered_at'])
            ->showFields(['event_id', 'event_date', 'status', 'registered_at', 'notes'])
            ->displayAs(['event_id' => 'Event', 'event_date' => 'Date', 'status' => 'Status', 'registered_at' => 'Registered on', 'notes' => 'Notes'])
            ->setRelation('event_id', 'kpdemo_events', 'title')
            ->fieldType('status', 'select', ['options' => Attendances::STATUSES])
            ->callbackColumn('status', static fn ($value) => '<span class="badge text-bg-' . Attendances::STATUS_COLORS[$value] . '">' . esc(Attendances::STATUSES[$value]) . '</span>')
            ->callbackColumn('event_date', static fn ($value, array $row) => isset($starts[$row['event_id']]) ? date('d/m/Y H:i', strtotime($starts[$row['event_id']])) : '')
            ->unsetAdd()
            ->unsetEdit()
            ->unsetDelete()
            ->filters(['status'])
            ->defaultOrder('registered_at', 'desc')
            // POST my/attendances/cancel/{masked id}: where(user_id) guarantees it is mine
            ->addOperation('cancel', fn (string $id, array $row, Crud $crud) => $this->cancelFuture([$id]) > 0
                ? 'Registration cancelled.'
                : $crud->backToList('danger', 'This registration cannot be cancelled.'), [
                'icon'    => 'bi bi-x-circle',
                'class'   => 'btn-outline-danger',
                'confirm' => 'Cancel this registration?',
                'visible' => static fn (array $row) => $row['status'] === 'registered' && $isFuture($row),
            ])
            ->addBulkAction('cancel', 'Cancel selected', function (array $ids) {
                // $ids only contains this user's records: the CRUD applies where() to bulk actions
                $total = $this->cancelFuture($ids);

                return "{$total} registrations cancelled (only active ones for upcoming events).";
            }, ['confirm' => 'Cancel the selected registrations?']);

        return $crud->render();
    }

    /**
     * Cancels the given registrations if they are active and the event has not started.
     *
     * @param list<int|string> $ids
     */
    private function cancelFuture(array $ids): int
    {
        $db = db_connect();
        $db->table('kpdemo_attendances')
            ->whereIn('id', $ids)
            ->where('status', 'registered')
            ->whereIn('event_id', static fn (BaseBuilder $events) => $events->select('id')->from('kpdemo_events')->where('starts_at >=', date('Y-m-d H:i:s')))
            ->update(['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')]);

        return $db->affectedRows();
    }
}
