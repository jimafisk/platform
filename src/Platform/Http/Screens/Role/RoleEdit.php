<?php

namespace Orchid\Platform\Http\Screens\Role;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Orchid\Platform\Core\Models\Role;
use Orchid\Platform\Facades\Alert;
use Orchid\Platform\Facades\Dashboard;

use Orchid\Platform\Screen\Layouts;
use Orchid\Platform\Screen\Link;
use Orchid\Platform\Screen\Screen;

use Orchid\Platform\Http\Layouts\Role\RoleEditLayout;

class RoleEdit extends Screen
{
    /**
     * Display header name
     *
     * @var string
     */
    public $name = 'dashboard::systems/roles.title';

    /**
     * Display header description
     *
     * @var string
     */
    public $description = 'dashboard::systems/roles.description';

    /**
     * Query data
     *
     * @param int $roleSlug
     *
     * @return array
     */
    public function query($roleSlug = null): array
    {
       
        $role = is_null($roleSlug) ? new Role : Role::where('slug', $roleSlug)->firstOrFail();
        
        $rolePermission = $role->permissions ?? [];
        $permission = Dashboard::getPermission();
        
        $permission->transform(function ($array) use ($rolePermission) {
            foreach ($array as $key => $value) {
                $array[$key]['active'] = array_key_exists($value['slug'], $rolePermission);
            }

            return $array;
        });


        return [
            'permission' => $permission,
            'role'       => $role,
        ];

    }

    /**
     * Button commands
     *
     * @return array
     */
    public function commandBar(): array
    {
        return [
            Link::name(' '.trans('dashboard::common.commands.save'))->icon('icon-check')->method('save'),
            Link::name(' '.trans('dashboard::common.commands.remove'))->icon('icon-trash')->method('remove'),
        ];
    }

    /**
     * Views
     *
     * @return array
     */

    public function layout(): array
    {
        return [
            Layouts::columns([
                'RoleEdit' => [
                    RoleEditLayout::class
                ],
            ]),

        ];
    }

    /**
     * @param         $slug
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save($slug, Request $request)
    {

        $role = Role::firstOrNew(['slug' => $slug]);
        

        $attributes = $request->get('role');

        $role->fill($attributes);

        foreach ($request->get('permissions',[]) as $key => $value){
                $permissions[base64_decode($key)] = 1;
        }

        $role->permissions = $permissions ?? [];
        $role->save();
        
        Alert::info(trans('dashboard::systems/roles.Role was saved'));

        return redirect()->route('dashboard.systems.roles');
    }

    /**
     * @param         $slug
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove($slug)
    {
        $role = Role::where('slug', $slug)->firstOrFail();

        $role->delete();

        Alert::info(trans('dashboard::systems/roles.Role was removed'));

        return redirect()->route('dashboard.systems.roles');
    }

}