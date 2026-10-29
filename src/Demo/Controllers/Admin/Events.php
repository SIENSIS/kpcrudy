<?php

namespace Kpcrudy\Demo\Controllers\Admin;

use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Controllers\BaseController;
use Kpcrudy\Demo\Controllers\Shared;
use Kpcrudy\Demo\Demo;
use Kpcrudy\Demo\Libraries\Auth;

class Events extends BaseController
{
    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'cancelled' => 'Cancelled'];

    private const STATUS_COLORS = ['draft' => 'secondary', 'published' => 'success', 'cancelled' => 'danger'];

    public const AUDIENCES = ['kids' => 'Children', 'teens' => 'Teenagers', 'adults' => 'Adults', 'seniors' => 'Seniors'];

    /**
     * Event management (administrators, and editors with the limits set in
     * Libraries\DemoPermissions).
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
            // Name of this CRUD for the central permissions (Libraries\DemoPermissions)
            ->setResource('events')
            ->setSubject('event', 'Events')
            ->columns(['poster', 'title', 'category_id', 'starts_at', 'location', 'status', 'price', 'occupancy'])
            ->fields(['title', 'category_id', 'summary', 'description', 'starts_at', 'ends_at', 'location', 'capacity', 'price', 'audience', 'status', 'poster'])
            ->showFields(['poster', 'title', 'category_id', 'summary', 'description', 'starts_at', 'ends_at', 'location', 'capacity', 'price', 'audience', 'status', 'occupancy', 'attendees', 'created_by', 'created_at', 'updated_at'])
            ->exportColumns(['id', 'title', 'category_id', 'starts_at', 'ends_at', 'location', 'capacity', 'price', 'audience', 'status', 'attendees'])
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
                'audience'    => 'Audience',
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
            // Several values in one column, stored as JSON: ["kids","adults"]
            ->fieldType('audience', 'multiselect', ['options' => self::AUDIENCES])
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

                // <progress> instead of a Bootstrap bar: no style="" attribute, so it works with a strict CSP
                return '<div class="small text-nowrap">' . $taken . ' / ' . $row['capacity'] . '</div>'
                    . '<progress class="kpdemo-occupancy kpdemo-' . $color . '" max="100" value="' . $percent . '">' . $percent . '%</progress>';
            })
            // CSV import (matched by id, so an exported file can be edited and imported again)
            ->enableImport()
            ->callbackImport('price', static fn (string $value) => strcasecmp($value, 'free') === 0 ? '0.00' : $value)
            ->filters(['category_id', 'status', 'audience', 'starts_at', 'price'])
            ->searchFields(['title', 'summary', 'location', 'category_id'])
            ->defaultOrder('starts_at', 'desc')
            ->beforeInsert(static function (array $data) use ($me, $checker) {
                $data['created_by'] = $me['id'];

                return $checker($data) ?? $data;
            })
            ->beforeUpdate(static fn (array $data) => $checker($data))
            // After any write (form, bulk action, import...): shown on the home page
            ->afterWrite(static function (string $operation, string $id, array $data, ?array $old) use ($me) {
                session()->set('kpdemo_last_change', [
                    'text' => ucfirst($operation) . ' of the event "' . ($data['title'] ?? $old['title'] ?? '#' . $id) . '"',
                    'user' => $me['first_name'],
                    'at'   => date('H:i:s'),
                ]);
            })
            // Hidden for editors: DemoPermissions does not give them 'attendees'
            ->addAction('Attendees', 'kpcrudy-demo/admin/attendances?f[event_id]={id}', ['icon' => 'bi bi-people', 'class' => 'btn-outline-info', 'permission' => 'attendees'])
            // Custom operation on the CRUD's own route (admin/events/duplicate/{masked id}):
            // kpcrudy checks CSRF, the masked id, permissions and where() before calling it
            ->addOperation('duplicate', function (string $id, array $event, Crud $crud) use ($me) {
                $now = date('Y-m-d H:i:s');
                $db  = db_connect();

                unset($event['id']);
                $db->table('kpdemo_events')->insert(array_merge($event, [
                    'title'      => mb_substr($event['title'] . ' (copy)', 0, 150),
                    'status'     => 'draft',
                    'poster'     => null,
                    'created_by' => $me['id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));

                return redirect()->to($crud->url('edit', (string) $db->insertID()))
                    ->with('message', 'A draft copy has been created. Review it and publish it when it is ready.');
            }, [
                'icon'    => 'bi bi-copy',
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
            // 'permission': $ids only contains the records the user can publish (editors: their own events)
            ->addBulkAction('publish', 'Publish', static function (array $ids) {
                db_connect()->table('kpdemo_events')->whereIn('id', $ids)->update(['status' => 'published']);

                return count($ids) . ' events published.';
            }, ['permission' => 'publish'])
            ->addBulkAction('cancel', 'Cancel', static function (array $ids) {
                db_connect()->table('kpdemo_events')->whereIn('id', $ids)->update(['status' => 'cancelled']);

                return count($ids) . ' events cancelled.';
            }, ['confirm' => 'Cancel the selected events?', 'permission' => 'cancel']);

        // Personal data only for administrators: one call hides the fields from the list, the
        // details page, the forms, search, filters, the CSV export and the import
        if (! Auth::isAdmin()) {
            $crud->hideFields(['attendees', 'created_by']);
        }

        return $crud->render();
    }
}
