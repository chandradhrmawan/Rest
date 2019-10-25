<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\OmUster\TmMenu;
use App\Models\OmUster\TrRole;

class RoleManagemnt{

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
    return response()->json([
      "result" => "Success, store role data",
      "data" => $store
    ]);
  }

  public static function storeRolePermesion($input){
    DB::connection('omuster')->table('TS_ROLE_MENU')->where('ROLE_ID', $input['ROLE_ID'])->delete();
    foreach ($input['MENU_ID'] as $id) {
      DB::connection('omuster')->table('TS_ROLE_MENU')->insert([
        "role_id"=>$input['ROLE_ID'],
        "menu_id"=>$id
      ]);
    }
    return response()->json([
      "result" => "Success, store role permission data",
    ]);
  }

	public static function permissionGet($input)
  {
      $role = static::role_data($input['ROLE_ID'],'permission');
      if ($role == false) {
        return response()->json(['response' => 'Fail, your role cant not found!']);
      }
      return response()->json(static::permission($role));
  }

  private static function role_data($role_id, $type){
    $role = DB::connection('omuster')->table('TR_ROLE')->where('ROLE_ID', $role_id)->first();
    if ($type == 'role') {
      return response()->json($role);
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
    $menu = TmMenu::with(['menu_has_child' => function($query) {
      return $query->orderBy('menu_order', 'asc');
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

}