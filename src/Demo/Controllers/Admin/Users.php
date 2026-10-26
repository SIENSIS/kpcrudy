<?php

namespace Kpcrudy\Demo\Controllers\Admin;

use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Controllers\BaseController;
use Kpcrudy\Demo\Demo;

class Users extends BaseController
{
    /**
     * User management (admins only).
     */
    public function index()
    {
        $me = $this->user();

        $crud = new Crud(new CrudConfig());
        $crud->setTable('kpdemo_users')
            ->setSubject('user', 'Users')
            ->columns(['avatar', 'username', 'full_name', 'email', 'role', 'active', 'interests', 'attendances'])
            ->fields(['username', 'password', 'first_name', 'last_name', 'email', 'avatar', 'birthdate', 'bio', 'role', 'active', 'interests'])
            ->showFields(['avatar', 'username', 'first_name', 'last_name', 'email', 'birthdate', 'bio', 'role', 'active', 'interests', 'attendances', 'created_at', 'updated_at'])
            ->displayAs([
                'avatar'      => 'Photo',
                'username'    => 'Username',
                'password'    => 'Password',
                'full_name'   => 'Full name',
                'first_name'  => 'First name',
                'last_name'   => 'Last name',
                'email'       => 'Email',
                'birthdate'   => 'Date of birth',
                'bio'         => 'About me',
                'role'        => 'Role',
                'active'      => 'Active',
                'interests'   => 'Interests',
                'attendances' => 'Registrations',
                'created_at'  => 'Joined',
                'updated_at'  => 'Last updated',
            ])
            ->fieldType('avatar', 'image', [
                'path'    => Demo::uploadPath('avatars'),
                'url'     => Demo::uploadUrl('avatars'),
                'maxSize' => 1024,
            ])
            ->fieldType('role', 'select', ['options' => ['admin' => 'Administrator', 'user' => 'User']])
            ->setRelationNN('interests', 'kpdemo_user_interests', 'kpdemo_categories', 'user_id', 'category_id', 'name')
            ->setRules([
                'username' => 'required|alpha_numeric_punct|min_length[3]|max_length[50]|is_unique[kpdemo_users.username,id,{id}]',
                'password' => 'required|min_length[8]',
                'email'    => 'required|valid_email|max_length[150]|is_unique[kpdemo_users.email,id,{id}]',
            ])
            ->readonlyFields(['username'])
            ->setHelp('password', 'At least 8 characters.')
            ->callbackColumn('full_name', static fn ($value, array $row) => esc($row['first_name'] . ' ' . $row['last_name']))
            ->callbackColumn('attendances', static function ($value, array $row) {
                $total = db_connect()->table('kpdemo_attendances')->where('user_id', $row['id'])->countAllResults();

                return '<span class="badge rounded-pill text-bg-light border">' . $total . '</span>';
            })
            ->searchFields(['username', 'first_name', 'last_name', 'email'])
            ->filters(['role', 'active', 'interests', 'birthdate'])
            ->defaultOrder('last_name')
            ->addAction('Registrations', 'kpcrudy-demo/admin/attendances?f[user_id]={id}', ['icon' => 'bi bi-calendar-check', 'class' => 'btn-outline-info'])
            ->addBulkAction('activate', 'Activate', static function (array $ids) {
                db_connect()->table('kpdemo_users')->whereIn('id', $ids)->update(['active' => 1]);

                return count($ids) . ' users activated.';
            })
            ->addBulkAction('deactivate', 'Deactivate', static function (array $ids) use ($me) {
                $ids = array_diff($ids, [(string) $me['id']]);

                if ($ids !== []) {
                    db_connect()->table('kpdemo_users')->whereIn('id', $ids)->update(['active' => 0]);
                }

                return count($ids) . ' users deactivated (never yourself).';
            }, ['confirm' => 'Deactivated users will not be able to log in to the demo. Continue?'])
            // CSV import: existing users are matched by username (to update them)
            ->enableImport(['key' => 'username'])
            ->setPermission('delete', static fn (?array $row) => $row === null || (int) $row['id'] !== (int) $me['id'])
            ->beforeUpdate(static function (array $data, string $id) use ($me) {
                if ((int) $id === (int) $me['id'] && (($data['role'] ?? 'admin') !== 'admin' || empty($data['active']))) {
                    return 'You cannot remove your own administrator role or deactivate yourself.';
                }

                return null;
            });

        return $crud->render();
    }
}
