<?php

namespace Kpcrudy\Demo\Controllers\Admin;

use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Controllers\BaseController;
use Kpcrudy\Demo\Models\CategoryModel;

class Categories extends BaseController
{
    /**
     * Categories, managed through a CodeIgniter model.
     */
    public function index()
    {
        $crud = new Crud(new CrudConfig());
        $crud->setModel(CategoryModel::class)
            ->setSubject('category', 'Categories')
            ->columns(['color', 'name', 'description', 'events'])
            ->fields(['name', 'color', 'description'])
            ->displayAs(['name' => 'Name', 'color' => 'Colour', 'description' => 'Description', 'events' => 'Events', 'created_at' => 'Created', 'updated_at' => 'Updated'])
            ->fieldType('color', 'color')
            ->callbackColumn('events', static function ($value, array $row) {
                $total = db_connect()->table('kpdemo_events')->where('category_id', $row['id'])->where('deleted_at', null)->countAllResults();

                return '<span class="badge rounded-pill text-bg-light border">' . $total . '</span>';
            })
            ->addAction('Events', 'kpcrudy-demo/admin/events?f[category_id]={id}', ['icon' => 'bi bi-calendar-event', 'class' => 'btn-outline-info'])
            ->beforeDelete(static function (string $id) {
                // Includes the events in the trash: the foreign key would block the delete anyway
                $total = db_connect()->table('kpdemo_events')->where('category_id', $id)->countAllResults();

                return $total > 0 ? "This category cannot be deleted: it has {$total} events." : null;
            })
            ->defaultOrder('name')
            ->unsetBulk()
            ->unsetExport();

        return $crud->render();
    }
}
