<?php

namespace Kpcrudy\Demo\Controllers;

use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Demo;

/**
 * The logged-in user's profile.
 */
class Profile extends BaseController
{
    /**
     * A one-record CRUD: where(id) limits it to the current user, so changing
     * the id in the URL never gives access to another profile.
     */
    public function index()
    {
        $me = $this->user();

        // There is no list: the profile page is the detail of the current user
        if (uri_string() === Demo::PREFIX . '/profile') {
            return redirect()->to(Demo::url('profile/show/' . $me['id']));
        }

        $crud = new Crud(new CrudConfig());
        $crud->setTable('kpdemo_users')
            ->setSubject('profile', 'My profile')
            ->where('id', $me['id'])
            ->setTheme('profile')
            ->setBackUrl(Demo::url('profile/show/' . $me['id']))
            ->unsetAdd()
            ->unsetDelete()
            ->unsetTrash()
            ->unsetExport()
            ->unsetBulk()
            ->editFields(['first_name', 'last_name', 'email', 'avatar', 'birthdate', 'bio', 'interests', 'password'])
            ->showFields(['avatar', 'username', 'first_name', 'last_name', 'email', 'birthdate', 'bio', 'interests', 'role', 'created_at'])
            ->displayAs([
                'avatar'     => 'Photo',
                'username'   => 'Username',
                'first_name' => 'First name',
                'last_name'  => 'Last name',
                'email'      => 'Email',
                'birthdate'  => 'Date of birth',
                'bio'        => 'About me',
                'interests'  => 'My interests',
                'password'   => 'New password',
                'role'       => 'Role',
                'created_at' => 'Member since',
            ])
            ->fieldType('avatar', 'image', ['path' => Demo::uploadPath('avatars'), 'url' => Demo::uploadUrl('avatars'), 'maxSize' => 1024])
            ->fieldType('role', 'select', ['options' => ['admin' => 'Administrator', 'user' => 'User']])
            ->setRelationNN('interests', 'kpdemo_user_interests', 'kpdemo_categories', 'user_id', 'category_id', 'name')
            ->setRules([
                'email'    => 'required|valid_email|is_unique[kpdemo_users.email,id,{id}]',
                'password' => 'min_length[8]',
            ])
            ->setHelp('interests', 'The categories you are interested in.');

        return $crud->render();
    }
}
