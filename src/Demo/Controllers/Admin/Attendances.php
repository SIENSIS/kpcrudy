<?php

namespace Kpcrudy\Demo\Controllers\Admin;

use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Controllers\BaseController;

class Attendances extends BaseController
{
    public const STATUSES = ['registered' => 'Registered', 'attended' => 'Attended', 'absent' => 'Absent', 'cancelled' => 'Cancelled'];

    public const STATUS_COLORS = ['registered' => 'primary', 'attended' => 'success', 'absent' => 'warning', 'cancelled' => 'secondary'];

    /**
     * Attendance records: the users <-> events junction table with its own data.
     */
    public function index()
    {
        $crud = new Crud(new CrudConfig());
        $crud->setTable('kpdemo_attendances')
            ->setSubject('registration', 'Event attendance')
            ->columns(['event_id', 'user_id', 'status', 'registered_at', 'notes'])
            ->addFields(['event_id', 'user_id', 'status', 'registered_at', 'notes'])
            ->editFields(['event_id', 'user_id', 'status', 'registered_at', 'notes'])
            ->readonlyFields(['event_id', 'user_id'])
            ->displayAs([
                'event_id'      => 'Event',
                'user_id'       => 'User',
                'status'        => 'Status',
                'registered_at' => 'Registered on',
                'notes'         => 'Notes',
                'created_at'    => 'Created',
                'updated_at'    => 'Updated',
            ])
            ->setRelation('event_id', 'kpdemo_events', '{title}', [
                'where' => ['deleted_at' => null, 'status !=' => 'draft'],
                'order' => 'starts_at DESC',
            ])
            ->setRelation('user_id', 'kpdemo_users', '{last_name}, {first_name}', [
                'where' => ['deleted_at' => null],
                'order' => 'last_name',
            ])
            ->fieldType('status', 'select', ['options' => self::STATUSES])
            ->callbackColumn('status', static fn ($value) => '<span class="badge text-bg-' . self::STATUS_COLORS[$value] . '">' . esc(self::STATUSES[$value]) . '</span>')
            ->setDefault('status', 'registered')
            ->setDefault('registered_at', date('Y-m-d H:i:s'))
            ->filters(['event_id', 'user_id', 'status', 'registered_at'])
            ->defaultOrder('registered_at', 'desc')
            ->beforeInsert(static function (array $data) {
                $db = db_connect();

                $exists = $db->table('kpdemo_attendances')
                    ->where('event_id', $data['event_id'])
                    ->where('user_id', $data['user_id'])
                    ->countAllResults();

                if ($exists > 0) {
                    return 'This user is already registered for the event.';
                }

                $event = $db->table('kpdemo_events')->select('capacity')->where('id', $data['event_id'])->get()->getRowArray();
                $taken = $db->table('kpdemo_attendances')
                    ->where('event_id', $data['event_id'])
                    ->whereIn('status', ['registered', 'attended'])
                    ->countAllResults();

                if (! empty($event['capacity']) && $taken >= $event['capacity'] && $data['status'] !== 'cancelled') {
                    return 'The event is fully booked.';
                }

                return null;
            })
            ->addBulkAction('attended', 'Mark as attended', static function (array $ids) {
                db_connect()->table('kpdemo_attendances')->whereIn('id', $ids)->update(['status' => 'attended']);

                return count($ids) . ' registrations marked as attended.';
            })
            ->addBulkAction('absent', 'Mark as absent', static function (array $ids) {
                db_connect()->table('kpdemo_attendances')->whereIn('id', $ids)->update(['status' => 'absent']);

                return count($ids) . ' registrations marked as absent.';
            });

        return $crud->render();
    }
}
