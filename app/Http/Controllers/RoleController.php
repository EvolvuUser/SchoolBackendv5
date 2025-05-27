<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Models\RolesAndMenu;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\Rule;
use DB;
class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all(); 
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'role_id' => 'required|string|max:255|unique:role_master,role_id',
            'rolename' => 'required|string|max:255|unique:role_master,name',
        ]);

        $role = Role::create([
            'role_id'=>$request->role_id,
            'name'=>$request->rolename
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $role
        ], 201); 
    }

    public function edit($role_id)
    {
        $role = Role::find($role_id);

        if ($role) {
            return response()->json([
                'success' => true,
                'data' => $role
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Role not found.'
        ], 404);
    }

    public function update(Request $request, $role_id)
    {
        $validatedData = $request->validate([
            'rolename' => [ 'required','string', 'max:50',Rule::unique('role_master', 'name')->ignore($role_id, 'role_id'),],
            'role_id' =>  [ 'required','string', 'max:1',Rule::unique('role_master', 'role_id')->ignore($role_id, 'role_id'),],
        ]);

        $role = Role::find($role_id);
        if(!$role){
            return response()->json([
            'success' => false,
            'message' => 'Role not found.'
        ], 404);
            
        }

            // Update the role
            $role->update([
                'name'=>$request->rolename,
                'role_id'=>$request->role_id
                ]);
            

        return response()->json([
            'status'=>200,
            'success' => true,
            'message' => 'Role updated successfully.'
        ]);
    }

    public function delete($role_id)
    {
        $tables = [
        'user_master' => 'role_id',
        'roles_and_menus' => 'role_id',
        'role_menu' => 'role_id',
        'roles' => 'role_id',
        'service_type' => 'role_id',
        'appointment_window' => 'role_id',
        'transport_usermaster' => 'role_id',
        ];
    
        
        foreach ($tables as $table => $column) {
            $exists = DB::table($table)->where($column, $role_id)->exists();
            if ($exists) {
                return response()->json([
                    'status'=>400,
                    'success' => false,
                    'message' => "Role cannot be deleted as it is being used in another table."
                ], 400);
            }
        }
        $role = Role::find($role_id);

        if ($role) {
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.'
            ]);
        }

        return response()->json([
             'status'=>400,
            'success' => false,
            'message' => 'Role not found.'
        ]);
    }

    public function showRoles()
    {
        $data = Role::all();
        return response()->json($data);
    }

    public function showAccess($role_id) {
        $role = Role::find($role_id);
        $menuList = Menu::all(); 

        $assignedMenuIds = RolesAndMenu::where('role_id', $role_id)
                                      ->pluck('menu_id')
                                      ->toArray();

        return response()->json([
            'role' => $role,
            'menuList' => $menuList,
            'assignedMenuIds' => $assignedMenuIds, 
        ]);
    }

    public function updateAccess(Request $request, $role_id)
    {
        $request->validate([
            'menu_ids' => 'required|array',
            'menu_ids.*' => 'exists:menus,menu_id',
        ]);

        RolesAndMenu::where('role_id', $role_id)->delete();
        $menuIds = $request->input('menu_ids');
        foreach ($menuIds as $menuId) {
            RolesAndMenu::create([
                'role_id' => $role_id,
                'menu_id' => $menuId,
            ]);
        }

        return response()->json(['message' => 'Access updated successfully']);
    }
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }


    public function navMenulist(Request $request)
{
    // $roleId = 3;
    $user = $this->authenticateUser();
    $roleId = $user->role_id;

    // Get the menu IDs from RolesAndMenu where role_id is the specified value
    $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
        ->pluck('menu_id')
        ->toArray();

    // Get the parent menus where parent_id is 0 and order by sequence
    $parentMenus = Menu::where('parent_id', 0)
        ->whereIn('menu_id', $assignedMenuIds)
        ->orderBy('sequence')
        ->get(['menu_id', 'name', 'url']);

    // Prepare the final response structure
    $menuList = $parentMenus->map(function ($parentMenu) use ($assignedMenuIds) {
        return [
            'menu_id' => $parentMenu->menu_id,
            'name' => $parentMenu->name,
            'url' => $parentMenu->url,
            'sub_menus' => $this->getSubMenus($parentMenu->menu_id, $assignedMenuIds)
        ];
    });

    return response()->json($menuList);
}

public function getSubMenus($parentId, $assignedMenuIds)
{
    // Get the submenus where parent_id is the given parent ID and order by sequence
    $subMenus = Menu::where('parent_id', $parentId)
        ->whereIn('menu_id', $assignedMenuIds)
        ->orderBy('sequence')
        ->get(['menu_id', 'name', 'url']);

    // Recursively get each submenu's submenus
    return $subMenus->map(function ($subMenu) use ($assignedMenuIds) {
        return [
            'menu_id' => $subMenu->menu_id,
            'name' => $subMenu->name,
            'url' => $subMenu->url,
            'sub_menus' => $this->getSubMenus($subMenu->menu_id, $assignedMenuIds)
        ];
    });
}


    //Menu Methods 
     public function getMenus()
    {
        $menus = Menu::orderBy('sequence')->get();

        // Step 2: Create a lookup array of menu_id => name
        $menuNames = $menus->pluck('name', 'menu_id');
        
        // Step 3: Add parent_name to each item
        $menusWithParentName = $menus->map(function ($menu) use ($menuNames) {
            $menu->parent_name = $menu->parent_id == 0 
                ? 'None' 
                : ($menuNames[$menu->parent_id] ?? 'Unknown');
            return $menu;
        });
        return response()->json($menus);
    }

     public function storeMenus(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer',
            'sequence' => [
                'required',
                'integer',
                Rule::unique('menus')->where(function ($query) use ($request) {
                    return $query->where('parent_id', $request->parent_id);
                }),
            ],
        ]);

        $validated['parent_id'] = $validated['parent_id'] ?? 0;


        $menu = Menu::create($validated);
        return response()->json($menu, 201);
    }

 

    public function showMenus($id)
{  
    $menu = Menu::findOrFail($id);
    $menu->parent_id_display = $menu->parent_id == 0 ? 'None' : $menu->parent_id;
    return response()->json($menu);
}


 

    public function updateMenus(Request $request, $id)
    {
        
        $menu = Menu::findOrFail($id);
        $validated = $request->validate([
                'name' => 'required|string|max:255',
                'url' => 'nullable|string|max:255',
                'parent_id' => 'nullable|integer',
                'sequence' => [
                    'nullable',
                    'integer',
                    Rule::unique('menus')
                        ->where(function ($query) use ($request) {
                            return $query->where('parent_id', $request->parent_id);
                        })
                        ->ignore($id, 'menu_id'), // exclude current record
                ],
            ]);
                        
                        $validated['parent_id'] = $validated['parent_id'] ?? 0;
                    
                        $menu->update($validated);
                        return response()->json($menu, 200);
        
    }
    


    public function destroy($menu_id)
    {
         $menu = Menu::find($menu_id);
        if($menu){
            $parent_id = $menu->parent_id;
            if($parent_id == '0'){
                 $childmenu = DB::table('menus')->where('parent_id',$menu_id)->exists();
                 if ($childmenu) {
                            return response()->json([
                                'status'=>400,
                                'success' => false,
                                'message' => "Menu cant be deleted because it has submenus."
                            ], 400);
                        }
                        
                        $rolesmenus = DB::table('roles_and_menus')->where('menu_id',$menu_id)->exists();
                        if ($rolesmenus) {
                            return response()->json([
                                'status'=>400,
                                'success' => false,
                                'message' => "Menu cant be deleted because it is used in access."
                            ], 400);
                        }
                        $menu->delete();
                        
                         return response()->json([
                                'status'=>200,
                                'success' => true,
                                'message' => "Menu deleted successfully."
                            ], 200);
                        
                 
            }
            else{
                $childmenu = DB::table('menus')->where('parent_id',$menu_id)->exists();
                 if ($childmenu) {
                            return response()->json([
                                'status'=>400,
                                'success' => false,
                                'message' => "Menu cant be deleted because it has submenus."
                            ], 400);
                        }
                        
                       $rolesmenus= DB::table('roles_and_menus')->where('menu_id',$menu_id)->exists();
                        if ($rolesmenus) {
                            return response()->json([
                                'status'=>400,
                                'success' => false,
                                'message' => "Menu cant be deleted because it is used in access."
                            ], 400);
                        }
                        
                        $menu->delete();
                         return response()->json([
                                'status'=>200,
                                'success' => true,
                                'message' => "Menu deleted successfully."
                            ], 200);
                
            }
            
        }
        else{
            return response()->json([
                                'status'=>400,
                                'success' => true,
                                'message' => "Menu not found."
                            ], 400);
            
        }
    }

    //API for the Roles  Dev Name- Manish Kumar Sharma 12-05-2025
    public function update_activeinactiverole($role_id){
        $role = Role::find($role_id);
        if($role){
            if($role->is_active == 'Y'){
                $tables = [
                    'user_master' => 'role_id',
                    'roles_and_menus' => 'role_id',
                    'role_menu' => 'role_id',
                    'roles' => 'role_id',
                    'service_type' => 'role_id',
                    'appointment_window' => 'role_id',
                    'transport_usermaster' => 'role_id',
                    ];
                
                    
                    foreach ($tables as $table => $column) {
                        $exists = DB::table($table)->where($column, $role_id)->exists();
                        if ($exists) {
                            return response()->json([
                                'status'=>400,
                                'success' => false,
                                'message' => "Role cannot be deactivated as it is being used in another table."
                            ], 400);
                        }
                    }
                    
                    $role->update([
                'is_active'=>'N'
                ]);
                return response()->json([
                                'status'=>200,
                                'success' => true,
                                'message' => "Role has been deactivated."
                            ], 200);
                
            }
            else{
                $role->update([
                'is_active'=>'Y'
                ]);
                
                return response()->json([
                                'status'=>200,
                                'success' => true,
                                'message' => "Role has been activated."
                            ], 200);
            }
            
        }
        else{
            return response()->json([
            'status' =>400,
            'success' => false,
            'message' => 'Role not found.'
        ]);
            
        }
        
        
        
    }

    //API for the Roles and Menus  Dev Name- Manish Kumar Sharma 12-05-2025
    public function deleterolesandmenus($menu_id){
        $menu = Menu::find($menu_id);
        if($menu){
            $parent_id = $menu->parent_id;
            if($parent_id == '0'){
                 $childmenu = DB::table('menus')->where('parent_id',$menu_id)->exists();
                 if ($childmenu) {
                            return response()->json([
                                'status'=>400,
                                'success' => false,
                                'message' => "Menu cant be deleted because it has submenus."
                            ], 400);
                        }
                        $menu->delete();
                        DB::table('roles_and_menus')->where('menu_id',$menu_id)->delete();
                         return response()->json([
                                'status'=>200,
                                'success' => true,
                                'message' => "Menu deleted successfully."
                            ], 200);
                        
                 
            }
            else{
                $childmenu = DB::table('menus')->where('parent_id',$menu_id)->exists();
                 if ($childmenu) {
                            return response()->json([
                                'status'=>400,
                                'success' => false,
                                'message' => "Menu cant be deleted because it has submenus."
                            ], 400);
                        }
                        $menu->delete();
                        DB::table('roles_and_menus')->where('menu_id',$menu_id)->delete();
                         return response()->json([
                                'status'=>200,
                                'success' => true,
                                'message' => "Menu deleted successfully."
                            ], 200);
                
            }
            
        }
        else{
            return response()->json([
                                'status'=>400,
                                'success' => true,
                                'message' => "Menu not found."
                            ], 400);
            
        }
    }
    //API for the Maximum Sequence For Parent  Dev Name- Manish Kumar Sharma 26-05-2025
    public function getMaximumSequenceForParent(Request $request){
        $parent_id = $request->parent_id;
        $results = DB::table('menus')
                    ->select('parent_id', DB::raw('MAX(sequence) as max_sequence'))
                    ->groupBy('parent_id')
                    ->where('parent_id',$parent_id)
                    ->get();
                    return response()->json([
                        'status' =>200,
                        'data'=>$results,
                        'message' => 'Maximum sequence for parent.',
                        'success'=>true
                        ]);
        
    }

   

}
   // public function updateMenus(Request $request, $id)
    // {
    //     $menu = Menu::findOrFail($id);
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'url' => 'required|string|max:255',
    //         'parent_id' => 'nullable|integer|exists:menus,menu_id',
    //         'sequence' => 'required|integer|unique:menus,menu_id',
    //     ]);
        
    //     $validated['parent_id'] = $validated['parent_id'] ?? 0;


    //     $menu->update($validated);
    //     return response()->json($menu, 200);
    // }

       // public function showMenus($id)
    // {  
    //    $menu =  Menu::findOrFail($id);    
    //     return response()->json();

    // }


        // public function navMenulist(Request $request)
    // {
    //     // $roleId = 3;
    //     $user = $this->authenticateUser();
    //     $roleId = $user->role_id;

    //     // Get the menu IDs from RolesAndMenu where role_id is the specified value
    //     $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
    //         ->pluck('menu_id')
    //         ->toArray();

    //     // Get the parent menus where parent_id is 0
    //     $parentMenus = Menu::where('parent_id', 0)
    //         ->whereIn('menu_id', $assignedMenuIds)
    //         ->get(['menu_id', 'name', 'url']);

    //     // Prepare the final response structure
    //     $menuList = $parentMenus->map(function ($parentMenu) use ($assignedMenuIds) {
    //         return [
    //             'menu_id' => $parentMenu->menu_id,
    //             'name' => $parentMenu->name,
    //             'url' => $parentMenu->url,
    //             'sub_menus' => $this->getSubMenus($parentMenu->menu_id, $assignedMenuIds)
    //         ];
    //     });

    //     return response()->json($menuList);
    // }

    // public function getSubMenus($parentId, $assignedMenuIds)
    // {
    //     // Get the submenus where parent_id is the given parent ID
    //     $subMenus = Menu::where('parent_id', $parentId)
    //         ->whereIn('menu_id', $assignedMenuIds)
    //         ->get(['menu_id', 'name', 'url']);

    //     // Recursively get each submenu's submenus
    //     return $subMenus->map(function ($subMenu) use ($assignedMenuIds) {
    //         return [
    //             'menu_id' => $subMenu->menu_id,
    //             'name' => $subMenu->name,
    //             'url' => $subMenu->url,
    //             'sub_menus' => $this->getSubMenus($subMenu->menu_id, $assignedMenuIds)
    //         ];
    //     });
    // }