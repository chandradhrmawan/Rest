<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\OmUster\TmMenu;
use App\Models\OmUster\TrRole;
use App\Models\OmUster\TmUser;
use Illuminate\Support\Facades\Hash;

class UserAndRoleManagemnt{

  public static function storeUser($input){
    if (empty($input['user_id'])){
      $cek = DB::connection('omuster')->table('TM_USER')->where('user_name',$input['user_name'])->count();
      if ($cek > 0) {
        return [
          "Success" => false,
          "result" => "fail, user_name already exists"
        ];
      }
      $cek = DB::connection('omuster')->table('TM_USER')->where('user_nik',$input['user_nik'])->count();
      if ($cek > 0) {
        return [
          "Success" => false,
          "result" => "fail, user_nik already exists"
        ];
      }
    }

    $TS_ROLE_BRANCH = [
      'branch_id' => $input['user_branch_id'],
      'user_branch_code' => $input['user_user_branch_code']
    ];

    if (empty($input['user_id'])) {
      $set_data['user_passwd'] = Hash::make('cintaIPC');
      $newUser = new TmUser;
      $newUser->user_name = $input['user_name'];
      $newUser->user_nik = $input['user_nik'];
      $newUser->user_role = $input['user_role'];
      $newUser->user_branch_id = $input['user_branch_id'];
      $newUser->user_branch_code = $input['user_branch_code'];
      $newUser->user_full_name = $input['user_full_name'];
      $newUser->user_status = $input['user_status'];
      $newUser->save();
      $TS_ROLE_BRANCH['user_id'] = $newUser->user_id;
      DB::connection('omuster')->table('TS_ROLE_BRANCH')->insert($TS_ROLE_BRANCH);
    }else{
      $set_data = [
        'user_name' => $input['user_name'],
        'user_nik' => $input['user_nik'],
        'user_role' => $input['user_role'],
        'user_branch_id' => $input['user_branch_id'],
        'user_branch_code' => $input['user_branch_code'],
        'user_full_name' => $input['user_full_name'],
        'user_status' => $input['user_status']
      ];
      if (isset($input['user_password']) and !empty($input['user_password'])) {
        $set_data['user_passwd'] = Hash::make($input['user_password']);
      }
      DB::connection('omuster')->table('TM_USER')->where('user_id',$input['user_id'])->update($set_data);
      $TS_ROLE_BRANCH['user_id'] = $input['user_id'];
      $cek = DB::connection('omuster')->table('TS_ROLE_BRANCH')->where('user_id', $input['user_id'])->where('branch_id', $input['user_branch_id'])->where('branch_code', $input['user_branch_code'])->count();
      if ($cek = 0) {
        DB::connection('omuster')->table('TS_ROLE_BRANCH')->insert($TS_ROLE_BRANCH);
      }
    }
    return [
      "result" => "Success, store user data"
    ];
  }

  public static function changePasswordUser($input){
    DB::connection('omuster')->table('TM_USER')->where('user_id',$input['user_id'])->update([
        'user_passwd' => Hash::make($input['user_password'])
    ]);
    return [
      "result" => "Success, change password user data"
    ];
  }

  public static function storeRole($input){
    if (empty($input['ROLE_ID'])) {
      $store = new TrRole;
    }else{
      $store = TrRole::find($input['ROLE_ID']);
    }
    $store->role_name = $input['ROLE_NAME'];
    $store->role_status = $input['ROLE_STATUS'];
    $store->role_service = $input['ROLE_SERVICE'];
    $store->role_desc = $input['ROLE_DESC'];
    $store->save();
    return [
      "result" => "Success, store role data",
      "data" => $store
    ];
  }

  public static function storeRolePermesion($input){
    DB::connection('omuster')->table('TS_ROLE_MENU')->where('ROLE_ID', $input['ROLE_ID'])->delete();
    foreach ($input['MENU_ID'] as $id) {
      DB::connection('omuster')->table('TS_ROLE_MENU')->insert([
        "role_id"=>$input['ROLE_ID'],
        "menu_id"=>$id
      ]);
    }
    return [
      "result" => "Success, store role permission data",
    ];
  }

	public static function permissionGet($input){
      $role = static::role_data($input['ROLE_ID'],'permission');
      if ($role == false) {
        return ['Success' => false, 'response' => 'Fail, your role cant not found!'];
      }else if(empty($role['role_service'])){
        return ['Success' => false, 'response' => 'Fail, your role service is null!'];
      }
      return static::permission($role);
  }

  private static function role_data($role_id, $type = null){
    $role = DB::connection('omuster')->table('TR_ROLE')->where('ROLE_ID', $role_id)->first();
    if ($type == 'role') {
      return $role;
    }else{
      if (empty($role)) {
        return false;
      }else{
        return ['role_id' => $role->role_id, 'role_service' => $role->role_service];
      }
    }
  }

  private static function role_p($role_id, $menu_id){
    if ($role_id == 0) {
      return false;
    }
    $permi = DB::connection('omuster')->table('TS_ROLE_MENU')->where('role_id',$role_id)->where('menu_id',$menu_id)->first();
    if (empty($permi)) {
      return false;
    }else{
      return true;
    }
  }

  private static function permission($role){
    $menu = TmMenu::with(['menu_has_child' => function($query) use ($role) {
      return $query->where('menu_service', $role['role_service'])->orderBy('menu_order', 'asc');
    }])->whereNull('menu_parent_id')->where('menu_is_active', 1)->where('menu_service', $role['role_service'])->orderBy('menu_m_order', 'asc')->get();

    $estjs = [];
    foreach ($menu as $list) {
      if (count($list['menu_has_child']) > 0) {
        $addAg = [];
        foreach ($list['menu_has_child'] as $listSc) {
          if ($listSc->menu_is_active == 1) {
            $addAgAg = [];
            $finds = TmMenu::where('menu_parent_id', $listSc->menu_id)->where('menu_is_active', 1)->where('menu_service', $role['role_service'])->orderBy('menu_order', 'asc')->get();
            if (count($finds) == 0) {
              $add_th_k = "leaf";
              $add_th_v = true;
            }else{
              $add_th_k = "expanded";
              $add_th_v = true;
              foreach ($finds as $find) {
                $newSetAg = [
                  "menuId" => $find->menu_id,
                  "text" => $find->menu_name,
                  "leaf" => true,
                  "iconCls" => $find->menu_icon == null ? "" : $find->menu_icon,
                  "checked" => static::role_p($role['role_id'],$find->menu_id)
                ];
                $addAgAg[] = $newSetAg;
              }
            }
            $newSet = [
              "menuId" => $listSc->menu_id,
              "text" => $listSc->menu_name,
              "iconCls" => $listSc->menu_icon == null ? "" : $listSc->menu_icon,
              "checked" => static::role_p($role['role_id'],$listSc->menu_id)
            ];
            $newSet[$add_th_k] = $add_th_v;

            if (count($addAgAg) > 0) {
              $newSet['children'] = $addAgAg;
            }
            $addAg[] = $newSet;
          }
        }
        $add_sc_k = "expanded";
        $add_sc_v =  true;
      }else{
        $addAg = [];
        $add_sc_k = "leaf";
        $add_sc_v =  true;
      }

      $add = [
        "menuId" => $list->menu_id,
        "text" => $list->menu_name,
        "iconCls" => $list->menu_icon == null ? "" : $list->menu_icon,
        "checked" => static::role_p($role['role_id'],$list->menu_id)
      ];

      $add[$add_sc_k] = $add_sc_v;

      if (count($addAg) > 0) {
        $add['children'] = $addAg;
      }

      $estjs[] = $add;
    }

    return [
      "expanded" => true,
      "text" => "NPK BILLING",
      "iconCls" => "x-fa fa-desktop",
      "children" => $estjs
    ];
  }

  public static function menuTree($roll_id){
    $menu_id_allow = DB::connection('omuster')->table('TS_ROLE_MENU')->where('ROLE_ID', $roll_id)->orderBy('menu_id', 'asc')->pluck('menu_id');

    $role = static::role_data($roll_id);
    $menu = TmMenu::with(['menu_has_child' => function($query) use ($role,$menu_id_allow) {
      return $query->where('menu_service', $role['role_service'])->whereIn('menu_id',$menu_id_allow)->orderBy('menu_order', 'asc');
    }])->whereNull('menu_parent_id')->where('menu_is_active', 1)->where('menu_service', $role['role_service'])->whereIn('menu_id',$menu_id_allow)->orderBy('menu_m_order', 'asc')->get();

    $estjs = [];
    foreach ($menu as $list) {
      if (count($list['menu_has_child']) > 0) {
        $addAg = [];
        foreach ($list['menu_has_child'] as $listSc) {
          if ($listSc->menu_is_active == 1) {
            $addAgAg = [];
            $finds = TmMenu::where('menu_parent_id', $listSc->menu_id)->where('menu_is_active', 1)->where('menu_service', $role['role_service'])->whereIn('menu_id',$menu_id_allow)->orderBy('menu_order', 'asc')->get();
            if (count($finds) == 0) {
              $add_th = [
                "leaf" => true
              ];
            }else{
              $add_th = [
                "expanded" => false,
                "selectable" =>false
              ];
              foreach ($finds as $find) {
                $newSetAg = [
                  "leaf" => true,
                  "text" => $find->menu_name,
                  "iconCls" => $find->menu_icon == null ? "" : $find->menu_icon,
                  "viewType" => $find->menu_link
                ];
                $addAgAg[] = $newSetAg;
              }
            }
            $newSet = [
              "text" => $listSc->menu_name,
              "iconCls" => $listSc->menu_icon == null ? "" : $listSc->menu_icon,
              "viewType" => $listSc->menu_link
            ];

            foreach ($add_th as $key => $value) {
              $newSet[$key] = $value;
            }

            if (count($addAgAg) > 0) {
              $newSet['children'] = $addAgAg;
            }
            $addAg[] = $newSet;
          }
        }
        $add_sc = [
          "expanded" => false,
          "selectable" =>false
        ];
      }else{
        $addAg = [];
        $add_sc = [
          "leaf" => true,
        ];
      }

      $add = [
        "text" => $list->menu_name,
        "iconCls" => $list->menu_icon == null ? "" : $list->menu_icon,
        "viewType" => $list->menu_link
      ];

      if ($list->menu_name == 'Dashboard') {
        $add['routeId'] = 'dashboard';
      }

      foreach ($add_sc as $key => $value) {
        $add[$key] = $value;
      }

      if (count($addAg) > 0) {
        $add['children'] = $addAg;
      }

      $estjs[] = $add;
    }

    // return [
    //   "root" => [
    //     "expanded" =>true,
    //     "children" => $estjs
    //   ]
    // ];
    return [
      "expanded" =>true,
      "children" => $estjs
    ];
  }

  public static function listRoleBranch($input){
    $rb = DB::connection('omuster')->table('TS_ROLE_BRANCH');
    if (!empty($input["condition"]["USER_ID"])) {
      $rb->where('USER_ID',$input["condition"]["USER_ID"]);
    }
    if (!empty($input["condition"]["ROLE_ID"])) {
      $rb->where('ROLE_ID',$input["condition"]["ROLE_ID"]);
    }
    if (!empty($input["condition"]["BRANCH_ID"])) {
      $rb->where('BRANCH_ID',$input["condition"]["BRANCH_ID"]);
    }
    if (!empty($input["condition"]["BRANCH_CODE"])) {
      $rb->where('BRANCH_CODE',$input["condition"]["BRANCH_CODE"]);
    }
    $count = $rb->count();

    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $rb->skip($input['start'])->take($input['limit']);
      }
    }
    $rb = $rb->get();

    $result = [];
    foreach ($rb as $rbl) {
      $newDt = [];

      foreach ($rbl as $key => $value) {
        $newDt[$key] = $value;
      }
      $user = DB::connection('omuster')->table('TM_USER')->select('user_name','user_full_name','user_nik')->where('user_id', $rbl->user_id)->take(1)->get();
      foreach ($user as $user) {
        foreach ($user as $key => $value) {
          $newDt[$key] = $value;
        }
      }
      $role = DB::connection('omuster')->table('TR_ROLE')->leftJoin('TM_REFF', 'role_service', '=', 'reff_id')->select('role_name', 'reff_name as role_service_name')->where('role_id', $rbl->role_id)->where('reff_tr_id', 1)->take(1)->get();
      foreach ($role as $role) {
        foreach ($role as $key => $value) {
          $newDt[$key] = $value;
        }
      }
      $branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id', $rbl->branch_id)->where('branch_code', $rbl->branch_code)->take(1)->get();
      foreach ($branch as $branch) {
        foreach ($branch as $key => $value) {
          $newDt[$key] = $value;
        }
      }
      $result[] = $newDt;
    }

    return ["result"=>$result, "count"=>$count];
  }

  public static function deleteRoleBranch($input)
  {
    DB::connection('omuster')->table('TS_ROLE_BRANCH')->where('USER_ID',$input["USER_ID"])->where('ROLE_ID',$input["ROLE_ID"])->where('BRANCH_ID',$input["BRANCH_ID"])->where('BRANCH_CODE',$input["BRANCH_CODE"])->delete();

    return [
      "result" => "Success, delete user role branch"
    ];
  }

}
