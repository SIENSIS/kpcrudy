<?php

namespace Kpcrudy\Demo\Controllers\Admin;

use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Controllers\BaseController;
use Kpcrudy\Demo\Controllers\Shared;
use Kpcrudy\Demo\Demo;

class Events extends BaseController
{
    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'cancelled' => 'Cancelled'];

    private const STATUS_COLORS = ['draft' => 'secondary', 'published' => 'success', 'cancelled' => 'danger'];

    /**
     * Event management (admins only).
     */
    public function index()
    {
        $me      = $this->user();
        $checker = static function (array $data) {
            if (! empty($data['ends_at']) && ! empty($data['starts_at']) && $data['ends_at'] < $data['starts_at']) {
                return 'The end date must be later than the start date.';
            }

            return null;
        };

        $crud = new Crud(new CrudConfig());
        $crud->setTable('kpdemo_events')
            ->setSubject('event', 'Events')
            ->columns(['poster', 'title', 'category_id', 'starts_at', 'location', 'status', 'price', 'occupancy'])
            ->fields(['title', 'category_id', 'summary', 'description', 'starts_at', 'ends_at', 'location', 'capacity', 'price', 'status', 'poster'])
            ->showFields(['poster', 'title', 'category_id', 'summary', 'description', 'starts_at', 'ends_at', 'location', 'capacity', 'price', 'status', 'occupancy', 'attendees', 'created_by', 'created_at', 'updated_at'])
            ->exportColumns(['id', 'title', 'category_id', 'starts_at', 'ends_at', 'location', 'capacity', 'price', 'status', 'attendees'])
            ->displayAs([
                'poster'      => 'Poster',
                'title'       => 'Title',
                'category_id' => 'Category',
                'summary'     => 'Short description',
                'description' => 'Long description',
                'starts_at'   => 'Starts',
                'ends_at'     => 'Ends',
                'location'    => 'Location',
                'capacity'    => 'Capacity',
                'price'       => 'Price',
                'status'      => 'Status',
                'occupancy'   => 'Occupancy',
                'attendees'   => 'Attendees',
                'created_by'  => 'Created by',
                'created_at'  => 'Created',
                'updated_at'  => 'Updated',
            ])
            ->setRelation('category_id', 'kpdemo_categories', '{name}')
            ->setRelation('created_by', 'kpdemo_users', '{first_name} {last_name}')
            ->setRelationNN('attendees', 'kpdemo_attendances', 'kpdemo_users', 'event_id', 'user_id', '{first_name} {last_name}')
            ->fieldType('poster', 'image', [
                'path'   => Demo::uploadPath('posters'),
                'url'    => Demo::uploadUrl('posters'),
                // One folder per year and month of the event: posters/2026/11/abc123.png
                'subdir' => static fn (array $data) => date('Y/m', strtotime($data['starts_at'] ?? 'now')),
            ])
            ->fieldType('status', 'select', ['options' => self::STATUSES])
            ->fieldType('description', 'textarea', ['attrs' => ['rows' => 8]])
            ->fieldType('price', 'decimal', ['attrs' => ['min' => 0]])
            ->fieldType('capacity', 'number', ['attrs' => ['min' => 1]])
            ->setRules('capacity', 'permit_empty|is_natural_no_zero')
            ->setHelp('capacity', 'Leave empty if there is no limit.')
            ->setDefault('status', 'draft')
            ->setDefault('price', '0.00')
            ->callbackColumn('status', static fn ($value) => '<span class="badge text-bg-' . self::STATUS_COLORS[$value] . '">' . esc(self::STATUSES[$value]) . '</span>')
            ->callbackColumn('price', static fn ($value) => (float) $value > 0 ? '€' . esc(number_format((float) $value, 2)) : '<span class="text-success">Free</span>')
            ->callbackExport('price', static fn ($value) => number_format((float) $value, 2, '.', ''))
            ->callbackColumn('occupancy', static function ($value, array $row) {
                $taken = db_connect()->table('kpdemo_attendances')
                    ->where('event_id', $row['id'])
                    ->whereIn('status', ['registered', 'attended'])
                    ->countAllResults();

                if (empty($row['capacity'])) {
                    return $taken . ' <span class="text-body-secondary">/ ∞</span>';
                }

                $percent = min(100, (int) round($taken * 100 / $row['capacity']));
                $color   = $percent >= 100 ? 'danger' : ($percent >= 75 ? 'warning' : 'primary');

                return '<div class="small text-nowrap">' . $taken . ' / ' . $row['capacity'] . '</div>'
                    . '<div class="progress" style="height: 6px; min-width: 80px"><div class="progress-bar bg-' . $color . '" style="width: ' . $percent . '%"></div></div>';
            })
            // CSV import (matched by id, so an exported file can be edited and imported again)
            ->enableImport()
            ->callbackImport('price', static fn (string $value) => strcasecmp($value, 'free') === 0 ? '0.00' : $value)
            ->filters(['category_id', 'status', 'starts_at', 'price'])
            ->searchFields(['title', 'summary', 'location', 'category_id'])
            ->defaultOrder('starts_at', 'desc')
            ->beforeInsert(static function (array $data) use ($me, $checker) {
                $data['created_by'] = $me['id'];

                return $checker($data) ?? $data;
            })
            ->beforeUpdate(static fn (array $data) => $checker($data))
            ->addAction('Attendees', 'kpcrudy-demo/admin/attendances?f[event_id]={id}', ['icon' => 'bi bi-people', 'class' => 'btn-outline-info'])
            ->addAction('Duplicate', 'kpcrudy-demo/admin/events-duplicate/{kp_id}', [
                'icon'    => 'bi bi-copy',
                'method'  => 'post',
                'confirm' => 'Create a draft copy of this event?',
            ])
            ->addHeaderAction('Public agenda', 'kpcrudy-demo/agenda', ['icon' => 'bi bi-calendar3', 'class' => 'btn-outline-secondary'])
            // Signed links (valid 7 days) to a read-only agenda that needs no login
            ->addHeaderAction('Share agenda', Shared::link(), ['icon' => 'bi bi-share', 'class' => 'btn-outline-secondary', 'target' => '_blank'])
            ->addAction('Share', static fn (array $row) => Shared::link($row['id']), [
                'icon'    => 'bi bi-share',
                'target'  => '_blank',
                'visible' => static fn (array $row) => $row['status'] === 'published' && $row['starts_at'] >= date('Y-m-d H:i:s'),
            ])
            ->addBulkAction('publish', 'Publish', static function (array $ids) {
                db_connect()->table('kpdemo_events')->whereIn('id', $ids)->update(['status' => 'published']);

                return count($ids) . ' events published.';
            })
            ->addBulkAction('cancel', 'Cancel', static function (array $ids) {
                db_connect()->table('kpdemo_events')->whereIn('id', $ids)->update(['status' => 'cancelled']);

                return count($ids) . ' events cancelled.';
            }, ['confirm' => 'Cancel the selected events?']);

        return $crud->render();
    }

    /**
     * Custom functionality reached from a row action: not part of the CRUD.
     */
    public function duplicate(string $code)
    {
        // The action URL carries the masked id ({kp_id}), like every other demo URL
        $id    = Demo::unmaskId('kpdemo_events', $code) ?? 0;
        $db    = db_connect();
        $event = $db->table('kpdemo_events')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if ($event === null) {
            return redirect()->to(Demo::url('admin/events'))->with('error', 'The event does not exist.');
        }

        unset($event['id']);
        $now = date('Y-m-d H:i:s');

        $db->table('kpdemo_events')->insert(array_merge($event, [
            'title'      => mb_substr($event['title'] . ' (copy)', 0, 150),
            'status'     => 'draft',
            'poster'     => null,
            'created_by' => $this->user()['id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        return redirect()->to(Demo::url('admin/events/edit/' . Demo::maskId('kpdemo_events', $db->insertID())))
            ->with('message', 'A draft copy has been created. Review it and publish it when it is ready.');
    }
}
