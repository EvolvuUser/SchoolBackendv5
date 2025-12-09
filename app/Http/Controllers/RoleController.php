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
use Illuminate\Support\Facades\Validator;
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
        $rolesQuery = DB::table('role_master')
        ->select('role_id', 'name','is_active', DB::raw("'role' as type"));

        $specialRolesQuery = DB::table('special_role_master')
            ->select('sp_role_id', 'name','is_active', DB::raw("'special_role' as type"));
        
        $unionQuery = $rolesQuery->union($specialRolesQuery)->get();
        return response()->json($unionQuery);
    }

    // public function showAccess($role_id) {
    //     $role = Role::find($role_id);
    //     $menuList = Menu::all(); 

    //     $assignedMenuIds = RolesAndMenu::where('role_id', $role_id)
    //                                   ->pluck('menu_id')
    //                                   ->toArray();

    //     return response()->json([
    //         'role' => $role,
    //         'menuList' => $menuList,
    //         'assignedMenuIds' => $assignedMenuIds, 
    //     ]);
    // }

    //Updated on 15-07-2025 by manish kumar sharma
    public function showAccess($role_id) {
        $role = Role::find($role_id);
        $menuList = DB::select("WITH RECURSIVE menu_paths AS (
        SELECT
            menu_id,
            name,
            parent_id,
            name AS full_path
        FROM menus
        WHERE parent_id = 0

        UNION ALL

        -- Recursive case: join children to their parents
        SELECT
            m.menu_id,
            m.name,
            m.parent_id,
            CONCAT(mp.full_path, '/', m.name) AS full_path
        FROM menus m
        JOIN menu_paths mp ON m.parent_id = mp.menu_id
    )

    SELECT * FROM menu_paths
    ORDER BY full_path;"); 

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
    
    $user = $this->authenticateUser();
    $roleId = $user->role_id;

    
    $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
        ->pluck('menu_id')
        ->toArray();

    
    $parentMenus = Menu::where('parent_id', 0)
        ->whereIn('menu_id', $assignedMenuIds)
        ->orderBy('sequence')
        ->get(['menu_id', 'name', 'url']);

    
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

public function navMenulisttest(Request $request)
{
    // $roleId = 3;
    $user = $this->authenticateUser();
    $roleId = $user->role_id;
    $customClaims = JWTAuth::getPayload()->get('academic_year');
    $roleIds = DB::table('department_special_role')
                   ->where('teacher_id',$user->reg_id)
                   ->where('academic_yr',$customClaims)
                   ->pluck('role')
                   ->toArray();
    
    $assignedMenuIdsss = RolesAndMenu::whereIn('role_id', $roleIds)
    ->pluck('menu_id')
    ->unique()
    ->toArray();

    
    $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
        ->pluck('menu_id')
        ->toArray();
    if ($roleId === 'T') {
    $hasClass = DB::table('class_teachers as n')
        ->where('n.teacher_id', $user->reg_id)
            ->whereIn('n.class_id', function ($query) {
                $query->select('class_id')->from('hpc_classes');
            })
            ->exists();
    
        if ($hasClass) {
            $extraMenuIds = [414, 408,399,410,402,406,404,409,413]; 
            $assignedMenuIds = array_merge($assignedMenuIds, $extraMenuIds);
        }
    }

    $mergedMenuIds = array_unique(array_merge($assignedMenuIdsss, $assignedMenuIds));
    
    $parentMenus = Menu::where('parent_id', 0)
        ->whereIn('menu_id', $mergedMenuIds)
        ->orderBy('sequence')
        ->get(['menu_id', 'name', 'url']);

    
    $menuList = $parentMenus->map(function ($parentMenu) use ($mergedMenuIds) {
        return [
            'menu_id' => $parentMenu->menu_id,
            'name' => $parentMenu->name,
            'url' => $parentMenu->url,
            'sub_menus' => $this->getSubMenus($parentMenu->menu_id, $mergedMenuIds)
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

    //API for the Roles for Event  Dev Name- Manish Kumar Sharma 12-08-2025
    public function saveRolesForEvent(Request $request){
        $validator = Validator::make($request->all(), [
        'role_id' => [
            'required',
            Rule::unique('event_roles', 'role_id')
        ],
        'name' => 'required|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }
    
        DB::table('event_roles')->insert([
            'role_id' => $request->role_id,
            'name' => $request->name,
            'is_active' => 'Y'
        ]);
        
        return response()->json([
            'status' =>200,
            'message' => 'Role for event saved.',
            'success'=>true
            ]);
        
    }
    
    //API for the Roles for Event  Dev Name- Manish Kumar Sharma 12-08-2025
    public function getRolesForEvent(Request $request){
        $roles = DB::table('event_roles')->get();
        return response()->json([
            'status' =>200,
            'data' => $roles,
            'message' => 'Role for event saved.',
            'success'=>true
            ]);
        
    }
     //API for the Roles for Event  Dev Name- Manish Kumar Sharma 12-08-2025
    public function updateRolesForEvent(Request $request,$id){
        
        $updated = DB::table('event_roles')
        ->where('role_id', $id)
        ->update([
            'name' => $request->name,
        ]);
        
        return response()->json([
            'status' =>200,
            'message' => 'Role updated successfully.',
            'success'=>true
            ]);
        
    }
    //API for the Roles for Event  Dev Name- Manish Kumar Sharma 12-08-2025
    public function deleteRolesForEvent(Request $request,$id){
        
        $isRoleUsed = DB::table('events')
        ->whereRaw("FIND_IN_SET(?, login_type)", [$id])
        ->exists();

        if ($isRoleUsed) {
            return response()->json([
                'status' => 409,
                'message' => 'Cannot delete this role because it is already used in events.',
                'success' => false
            ], 409);
        }
        
        DB::table('event_roles')
        ->where('role_id', $id)
        ->delete();
        return response()->json([
            'status' =>200,
            'message' => 'Role deleted successfully.',
            'success'=>true
            ]);
    }
    
    public function updateActiveForEvent(Request $request,$id){
         $role = DB::table('event_roles')->where('role_id', $id)->first();

            if (!$role) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Role not found.',
                    'success' => false
                ], 404);
            }
        
            // Flip the status (if null, treat as inactive)
            $newStatus = ($role->is_active === 'Y') ? 'N' : 'Y';
        
            DB::table('event_roles')
                ->where('role_id', $id)
                ->update([
                    'is_active' => $newStatus
                ]);
        
            return response()->json([
                'status' => 200,
                'message' => 'Role status toggled successfully.',
                'data' => $newStatus,
                'success' => true
            ]);
    }
    
    public function getActiveRolesForEvent(Request $request){
        $roles = DB::table('event_roles')->where('is_active','Y')->get();
        return response()->json([
            'status' =>200,
            'data' => $roles,
            'message' => 'Role for event saved.',
            'success'=>true
            ]);
        
        
    }
    
    public function navLeafMenus(Request $request)
    {
        $user = $this->authenticateUser();
        $roleId = $user->role_id;
        $customClaims = JWTAuth::getPayload()->get('academic_year');
    
        // Get all assigned menu IDs for this role
        $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
            ->pluck('menu_id')
            ->toArray();
        if ($roleId === 'T') {
            $hasClass = DB::table('class_teachers as n')
                ->where('n.teacher_id', $user->reg_id)
                    ->whereIn('n.class_id', function ($query) {
                        $query->select('class_id')->from('hpc_classes');
                    })
                    ->exists();
            
                if ($hasClass) {
                    $extraMenuIds = [414, 408,399,410,402,406,404,409,413]; 
                    $assignedMenuIds = array_merge($assignedMenuIds, $extraMenuIds);
                }
            }
        // Get all menus assigned to the role
        $assignedMenus = Menu::whereIn('menu_id', $assignedMenuIds)->get();
    
        // Find all parent IDs among assigned menus
        $parentIds = $assignedMenus->pluck('parent_id')->unique()->filter()->toArray();
    
        // Leaf menus = menus that are assigned but NOT a parent of any other
        $leafMenus = $assignedMenus->whereNotIn('menu_id', $parentIds)
            ->sortBy('sequence')
            ->values()
            ->map(function ($menu) {
                return [
                    'menu_id' => $menu->menu_id,
                    'name'    => $menu->name,
                    'url'     => $menu->url,
                    'parent_id' => $menu->parent_id,
                ];
            });
    
        return response()->json([
            'status'=>200,
            'message'=>'leaf data',
            'data'=>$leafMenus,
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